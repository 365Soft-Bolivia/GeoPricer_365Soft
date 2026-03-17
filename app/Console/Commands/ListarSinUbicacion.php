<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class ListarSinUbicacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ubicaciones:listar-sin-ubicacion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lista los productos sin ubicación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('============================================');
        $this->info('  Productos SIN Ubicación');
        $this->info('============================================');
        $this->newLine();

        $productos = Product::doesntHave('location')
            ->select('id', 'name', 'codigo_inmueble', 'operacion', 'price_usd', 'price_bob', 'default_image')
            ->orderBy('name')
            ->get();

        if ($productos->isEmpty()) {
            $this->info('No hay productos sin ubicación.');
            return Command::SUCCESS;
        }

        $this->info("Total: {$productos->count()} productos sin ubicación");
        $this->newLine();

        $this->table(
            ['ID', 'Nombre', 'Código', 'Operación', 'Precio USD', 'Precio BOB'],
            $productos->map(fn($p) => [
                $p->id,
                substr($p->name, 0, 50),
                $p->codigo_inmueble,
                $p->operacion ?? 'N/A',
                $p->price_usd ?? 'N/A',
                $p->price_bob ?? 'N/A',
            ])
        );

        return Command::SUCCESS;
    }
}

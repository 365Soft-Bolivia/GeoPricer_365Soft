import { Folder, MapPinned, Tag, LockKeyhole, Upload, RefreshCw } from 'lucide-vue-next';
import { admin } from '@/routes-custom';
import type { NavItem } from '@/types';

const { dashboard, proyectos, categorias, accesos, ubicaciones, dataImport, dataReorder } = admin;

export const allMainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
    icon: MapPinned,
  },
  {
    title: 'Proyectos',
    href: proyectos.index().url,
    icon: Folder,
    roles: ['admin'],
  },
  {
    title: 'Ubicaciones',
    href: ubicaciones().url,
    icon: MapPinned,
    roles: ['admin'],
  },
  {
    title: 'Categorías',
    href: categorias().url,
    icon: Tag,
    roles: ['admin'],
  },
  {
    title: 'Accesos',
    href: accesos().url,
    icon: LockKeyhole,
    roles: ['admin'],
  },
  {
    title: 'Inyección de Datos',
    href: dataImport().url,
    icon: Upload,
    roles: ['admin'],
  },
  {
    title: 'Reordenar Datos',
    href: dataReorder().url,
    icon: RefreshCw,
    roles: ['admin'],
  },
];

import { Folder, MapPinned, Tag, LockKeyhole, Upload, RefreshCw } from 'lucide-vue-next';
import type { NavItem } from '@/types';

// URLs directas - evitamos problemas de runtime con funciones dinámicas
export const allMainNavItems: NavItem[] = [
  // {
  //   title: 'Dashboard',
  //   href: '/admin/dashboard',
  //   icon: MapPinned,
  // },
  {
    title: 'Proyectos',
    href: '/admin/proyectos',
    icon: Folder,
    roles: ['admin'],
  },
  {
    title: 'Ubicaciones',
    href: '/admin/ubicaciones',
    icon: MapPinned,
    roles: ['admin'],
  },
  // {
  //   title: 'Categorías',
  //   href: '/admin/categorias',
  //   icon: Tag,
  //   roles: ['admin'],
  // },
  {
    title: 'Inyección de Datos',
    href: '/admin/data-import',
    icon: Upload,
    roles: ['admin'],
  },
  {
    title: 'Reordenar Datos',
    href: '/admin/data-reorder',
    icon: RefreshCw,
    roles: ['admin'],
  },
  {
    title: 'Accesos',
    href: '/admin/accesos',
    icon: LockKeyhole,
    roles: ['admin'],
  },
];

import { Folder, MapPinned, Tag, LockKeyhole } from 'lucide-vue-next';
import { dashboard, proyectos,  categorias, accesos, ubicaciones } from '@/routes';
import type { NavItem } from '@/types';

export const allMainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
    icon: MapPinned,
  },
  {
    title: 'Proyectos',
    href: proyectos().url,
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
];

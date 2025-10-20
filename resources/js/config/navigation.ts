import { MapPinned, Tag, LockKeyhole, Users, Droplets  } from 'lucide-vue-next';
import { dashboard, terrenos, categorias, accesos } from '@/routes';
import type { NavItem } from '@/types';

export const allMainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
    icon: MapPinned,
  },
 {
    title: 'Propietarios',
    href: '/propietarios',
    icon: Users,
    roles: ['admin'],
  },
  {
    title: 'Lecturas',
    href: '/lecturas',
    icon: Droplets ,
    roles: ['admin'],
  },
  {
    title: 'Medidores',
    href: '/medidores',
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

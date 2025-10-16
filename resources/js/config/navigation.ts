import { Folder, MapPinned, MapPin, Tag, LockKeyhole, Users } from 'lucide-vue-next';
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
    title: 'Terrenos',
    href: terrenos().url,
    icon: MapPin,
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

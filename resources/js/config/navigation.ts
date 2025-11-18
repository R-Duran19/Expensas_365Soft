import { MapPinned, Tag, LockKeyhole, Users, Droplets, Calendar, FileText, CreditCard, ChevronDown, ChevronRight, Home, Waves, DollarSign, UserCircle } from 'lucide-vue-next';
import { dashboard, terrenos, categorias, accesos } from '@/routes';
import type { NavItem } from '@/types';

export const allMainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: dashboard().url,
    icon: Home,
  },
  {
    title: 'AGUA',
    icon: Waves,
    roles: ['admin'],
    children: [
      {
        title: 'Lecturas',
        href: '/lecturas',
        icon: Droplets,
        roles: ['admin'],
      },
      {
        title: 'Medidores',
        href: '/medidores',
        icon: Tag,
        roles: ['admin'],
      },
    ],
  },
  {
    title: 'EXPENSAS',
    icon: FileText,
    roles: ['admin'],
    children: [
      {
        title: 'Expensas',
        href: '/property-expenses',
        icon: DollarSign,
        roles: ['admin'],
      },
      {
        title: 'Períodos de Expensas',
        href: '/expense-periods',
        icon: Calendar,
        roles: ['admin'],
      },
    ],
  },
  {
    title: 'PAGOS',
    href: '/pagos',
    icon: CreditCard,
    roles: ['admin'],
  },
  {
    title: 'PERSONAS',
    icon: UserCircle,
    roles: ['admin'],
    children: [
      {
        title: 'Propietarios',
        href: '/propietarios',
        icon: Users,
        roles: ['admin'],
      },
      {
        title: 'Accesos',
        href: accesos().url,
        icon: LockKeyhole,
        roles: ['admin'],
      },
    ],
  },
];

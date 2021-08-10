const AdminHomeView = () => import('@admin/views/admin/AdminHomeView')
const PlatformView = () => import('@admin/views/admin/PlatformView')
const ToolView = () => import('@admin/views/admin/ToolView')
const UserView = () => import('@admin/views/admin/UserView')

const adminRoutes = [
  {
    path: '',
    name: 'admin',
    component: AdminHomeView
  },
  {
    path: 'platform',
    name: 'adminPlatform',
    component: PlatformView
  },
  {
    path: 'tool',
    name: 'adminTool',
    component: ToolView
  },
  {
    path: 'user',
    name: 'adminUser',
    component: UserView
  },
]

export default adminRoutes

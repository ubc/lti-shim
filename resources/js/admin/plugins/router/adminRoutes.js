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
    component: PlatformView,
    children: [
      {
        path: 'add',
        name: 'addPlatform',
        component: PlatformView
      },
      {
        path: ':platformId/edit',
        name: 'editPlatform',
        component: PlatformView
      }
    ]
  },
  {
    path: 'tool',
    name: 'adminTool',
    component: ToolView,
    children: [
      {
        path: 'add',
        name: 'addTool',
        component: ToolView
      },
      {
        path: ':toolId/edit',
        name: 'editTool',
        component: ToolView
      }
    ]
  },
  {
    path: 'user',
    name: 'adminUser',
    component: UserView,
    children: [
      {
        path: 'add',
        name: 'addUser',
        component: UserView
      },
      {
        path: ':userId/edit',
        name: 'editUser',
        component: UserView
      }
    ]
  },
]

export default adminRoutes

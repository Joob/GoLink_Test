const routesOthers = [
    {
        name: 'UploadDemo',
        path: '/upload-demo',
        component: () => import(/* webpackChunkName: "chunks/upload-demo" */ '../views/UploadDemo'),
        meta: {
            requiresAuth: false,
        },
    },
    {
        name: 'NotFound',
        path: '*',
        component: () => import(/* webpackChunkName: "chunks/not-found" */ '../views/NotFound'),
        meta: {
            requiresAuth: false,
        },
    },
    {
        name: 'TemporaryUnavailable',
        path: '/temporary-unavailable',
        component: () => import(/* webpackChunkName: "chunks/temporary-unavailable" */ '../views/TemporaryUnavailable'),
        meta: {
            requiresAuth: false,
        },
    },
]

export default routesOthers

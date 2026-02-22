import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    {
        path: '/',
        redirect: '/caregiver/patients',
    },
    // ── 의료진 뷰 ──────────────────────────────────────
    {
        path: '/caregiver',
        component: () => import('../views/caregiver/CaregiverLayout.vue'),
        children: [
            { path: 'patients',         component: () => import('../views/caregiver/PatientList.vue') },
            { path: 'patients/:id',     component: () => import('../views/caregiver/PatientDetail.vue') },
            { path: 'patients/new',     component: () => import('../views/caregiver/PatientForm.vue') },
        ],
    },
    // ── 환자 뷰 ────────────────────────────────────────
    {
        path: '/patient/:id',
        component: () => import('../views/patient/PatientLayout.vue'),
        children: [
            { path: '',          component: () => import('../views/patient/PatientDashboard.vue') },
            { path: 'activities', component: () => import('../views/patient/ActivityBoard.vue') },
            { path: 'chat',      component: () => import('../views/patient/PatientChat.vue') },
            { path: 'diary',     component: () => import('../views/patient/DiaryView.vue') },
            { path: 'wish-tree', component: () => import('../views/patient/WishTree.vue') },
            { path: 'assessment', component: () => import('../views/patient/EfficacyAssessment.vue') },
        ],
    },
]

export default createRouter({
    history: createWebHistory(),
    routes,
})

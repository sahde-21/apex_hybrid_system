<?php

use App\Models\LeaveRequest;
use App\Models\WorkflowDemoDocument;

return [

    /*
    |--------------------------------------------------------------------------
    | Enterprise Workflow Definitions
    |--------------------------------------------------------------------------
    |
    | Each key is a reusable workflow definition. Modules adopt the engine by
    | implementing Workflowable (or using HasWorkflow) and registering here.
    | Sales and Purchasing retain their dedicated workflow services.
    |
    */

    'definitions' => [

        'leave-requests' => [
            'label' => 'Leave Request',
            'model' => LeaveRequest::class,
            'status_column' => 'status',
            'initial_status' => 'draft',
            'module' => 'leave-requests',
            'show_route' => 'leave-requests.show',
            'transitions' => [
                'submit' => [
                    'from' => ['draft'],
                    'to' => 'pending',
                    'permissions' => ['workflow.submit', 'leave-requests.update'],
                    'notify' => 'submitted',
                    'notify_permission' => 'leave-requests.approve',
                ],
                'approve' => [
                    'from' => ['pending'],
                    'to' => 'approved',
                    'permissions' => ['workflow.approve', 'leave-requests.approve'],
                    'notify' => 'approved',
                    'notify_permission' => 'leave-requests.read',
                    'approval' => [
                        'mode' => 'sequential',
                        'levels' => [
                            [
                                'name' => 'hr_manager',
                                'label' => 'HR Manager',
                                'permissions' => ['leave-requests.approve', 'workflow.approve'],
                            ],
                        ],
                    ],
                ],
                'reject' => [
                    'from' => ['pending'],
                    'to' => 'rejected',
                    'permissions' => ['workflow.reject', 'leave-requests.approve'],
                    'requires_comment' => true,
                    'notify' => 'rejected',
                    'notify_permission' => 'leave-requests.read',
                    'clears_approvals' => true,
                ],
                'return' => [
                    'from' => ['pending'],
                    'to' => 'draft',
                    'permissions' => ['workflow.reject', 'leave-requests.approve'],
                    'requires_comment' => true,
                    'notify' => 'returned',
                    'notify_permission' => 'leave-requests.read',
                    'clears_approvals' => true,
                ],
                'cancel' => [
                    'from' => ['draft', 'pending'],
                    'to' => 'cancelled',
                    'permissions' => ['workflow.cancel', 'leave-requests.update'],
                    'notify' => 'cancelled',
                    'notify_permission' => 'leave-requests.read',
                    'clears_approvals' => true,
                ],
                'reopen' => [
                    'from' => ['rejected', 'cancelled'],
                    'to' => 'draft',
                    'permissions' => ['workflow.reopen', 'leave-requests.update'],
                    'notify' => null,
                    'clears_approvals' => true,
                ],
                'close' => [
                    'from' => ['approved'],
                    'to' => 'closed',
                    'permissions' => ['workflow.archive', 'leave-requests.update'],
                    'notify' => null,
                ],
                'archive' => [
                    'from' => ['closed', 'cancelled', 'rejected'],
                    'to' => 'archived',
                    'permissions' => ['workflow.archive'],
                    'notify' => null,
                ],
            ],
        ],

        'demo-multi-level' => [
            'label' => 'Demo Multi-Level',
            'model' => WorkflowDemoDocument::class,
            'status_column' => 'status',
            'initial_status' => 'draft',
            'module' => 'workflow',
            'show_route' => null,
            'transitions' => [
                'submit' => [
                    'from' => ['draft'],
                    'to' => 'pending_approval',
                    'permissions' => ['workflow.submit'],
                    'notify' => 'submitted',
                    'notify_permission' => 'workflow.approve',
                ],
                'approve' => [
                    'from' => ['pending_approval'],
                    'to' => 'approved',
                    'permissions' => ['workflow.approve'],
                    'notify' => 'approved',
                    'notify_permission' => 'workflow.approve',
                    'approval' => [
                        'mode' => 'sequential',
                        'levels' => [
                            ['name' => 'level_1', 'label' => 'Level 1', 'permissions' => ['workflow.approve']],
                            ['name' => 'level_2', 'label' => 'Level 2', 'permissions' => ['workflow.approve']],
                        ],
                    ],
                ],
                'reject' => [
                    'from' => ['pending_approval'],
                    'to' => 'rejected',
                    'permissions' => ['workflow.reject'],
                    'requires_comment' => true,
                    'notify' => 'rejected',
                    'notify_permission' => 'workflow.submit',
                    'clears_approvals' => true,
                ],
                'cancel' => [
                    'from' => ['draft', 'pending_approval'],
                    'to' => 'cancelled',
                    'permissions' => ['workflow.cancel'],
                    'notify' => 'cancelled',
                    'clears_approvals' => true,
                ],
            ],
        ],

        'demo-parallel' => [
            'label' => 'Demo Parallel',
            'model' => WorkflowDemoDocument::class,
            'status_column' => 'status',
            'initial_status' => 'pending_approval',
            'module' => 'workflow',
            'show_route' => null,
            'transitions' => [
                'approve' => [
                    'from' => ['pending_approval'],
                    'to' => 'approved',
                    'permissions' => ['workflow.approve'],
                    'notify' => 'approved',
                    'approval' => [
                        'mode' => 'parallel',
                        'levels' => [
                            ['name' => 'finance', 'label' => 'Finance', 'permissions' => ['workflow.approve']],
                            ['name' => 'ops', 'label' => 'Operations', 'permissions' => ['workflow.approve']],
                        ],
                    ],
                ],
                'reject' => [
                    'from' => ['pending_approval'],
                    'to' => 'rejected',
                    'permissions' => ['workflow.reject'],
                    'requires_comment' => true,
                    'clears_approvals' => true,
                    'notify' => 'rejected',
                ],
            ],
        ],
    ],
];

<?php
// Route Definitions

return [
    // Public routes - Authentication
    'GET /' => [
        'handler' => ['Public\AuthController', 'index'],
        'middleware' => []
    ],
    'POST /login' => [
        'handler' => ['Public\AuthController', 'login'],
        'middleware' => ['RateLimitMiddleware', 'CsrfMiddleware']
    ],
    // CHANGE: GET → POST + CSRF
    'POST /logout' => [
        'handler' => ['Public\AuthController', 'logout'],
        'middleware' => ['CsrfMiddleware']
    ],
    
    // Session heartbeat
    'POST /heartbeat' => [
        'handler' => ['Public\AuthController', 'heartbeat'],
        'middleware' => ['AuthMiddleware', 'CsrfMiddleware']
    ],
    
    // Voting routes
    'GET /vote' => [
        'handler' => ['Public\VoteController', 'index'],
        'middleware' => ['AuthMiddleware']
    ],
    'GET /vote/election-data' => [
        'handler' => ['Public\VoteController', 'getElectionData'],
        'middleware' => ['AuthMiddleware']
    ],
    'POST /vote/submit' => [
        'handler' => ['Public\VoteController', 'submitVote'],
        'middleware' => ['AuthMiddleware', 'CsrfMiddleware']
    ],
    'GET /vote/success' => [
        'handler' => ['Public\VoteController', 'success'],
        'middleware' => ['AuthMiddleware']
    ],
    'POST /vote/clear-session' => [
        'handler' => ['Public\VoteController', 'clearSession'],
        'middleware' => ['AuthMiddleware', 'CsrfMiddleware']
    ],

    'GET /vote/voter-details' => [
        'handler' => ['Public\VoteController', 'voterDetails'],
        'middleware' => ['AuthMiddleware']
    ],
    
    // Admin routes
    'POST /admin/heartbeat' => [
        'handler' => ['Admin\AuthController', 'heartbeat'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'GET /admin' => [
        'handler' => ['Admin\AuthController', 'showLogin'],
        'middleware' => []
    ],
    'POST /admin/login' => [
        'handler' => ['Admin\AuthController', 'login'],
        'middleware' => ['RateLimitMiddleware', 'CsrfMiddleware']
    ],
    'GET /admin/dashboard' => [
        'handler' => ['Admin\DashboardController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    // CHANGE: GET → POST + CSRF
    'POST /admin/logout' => [
        'handler' => ['Admin\AuthController', 'logout'],
        'middleware' => ['CsrfMiddleware']
    ],

    // Stats
    'GET /admin/api/stats' => [
        'handler' => ['Admin\ApiController', 'getStats'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    // Positions API
    'GET /admin/api/positions' => [
        'handler' => ['Admin\ApiController', 'getPositions'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/positions' => [
        'handler' => ['Admin\ApiController', 'savePosition'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/positions/delete' => [
        'handler' => ['Admin\ApiController', 'deletePosition'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/positions/bulk-delete' => [
        'handler' => ['Admin\ApiController', 'bulkDeletePositions'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Position delete check
    'POST /admin/api/positions/check-delete' => [
        'handler' => ['Admin\ApiController', 'checkPositionDelete'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Candidates API
    'GET /admin/api/candidates' => [
        'handler' => ['Admin\ApiController', 'getCandidates'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/candidates' => [
        'handler' => ['Admin\ApiController', 'saveCandidate'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/candidates/delete' => [
        'handler' => ['Admin\ApiController', 'deleteCandidate'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'GET /admin/api/candidates/options' => [
        'handler' => ['Admin\ApiController', 'getCandidateOptions'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/candidates/bulk-delete' => [
        'handler' => ['Admin\ApiController', 'bulkDeleteCandidates'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Candidate image upload
    'POST /admin/api/candidates/upload-image' => [
        'handler' => ['Admin\ApiController', 'uploadCandidateImage'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Partylists API
    'GET /admin/api/partylists' => [
        'handler' => ['Admin\ApiController', 'getPartylists'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/partylists' => [
        'handler' => ['Admin\ApiController', 'savePartylist'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/partylists/delete' => [
        'handler' => ['Admin\ApiController', 'deletePartylist'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/partylists/bulk-delete' => [
        'handler' => ['Admin\ApiController', 'bulkDeletePartylists'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Partylist delete check
    'POST /admin/api/partylists/check-delete' => [
        'handler' => ['Admin\ApiController', 'checkPartylistDelete'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    
    // Vote Lookup (sidebar search)
    'GET /admin/api/votes/search' => [
        'handler' => ['Admin\ResultsController', 'searchVotes'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/votes/detail' => [
        'handler' => ['Admin\ResultsController', 'voteDetail'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    // Results
    'GET /admin/results' => [
        'handler' => ['Admin\ResultsController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/results' => [
        'handler' => ['Admin\ResultsController', 'getResults'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/results/export' => [
        'handler' => ['Admin\ResultsController', 'exportCSV'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/results/download' => [
        'handler' => ['Admin\ResultsController', 'downloadCSV'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/results/delete' => [
        'handler' => ['Admin\ResultsController', 'deleteElectionData'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    'GET /admin/api/results/export-progress' => [
        'handler' => ['Admin\ResultsController', 'exportProgress'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    // Account Management
    'GET /admin/account_management' => [
        'handler' => ['Admin\AccountController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/users' => [
        'handler' => ['Admin\AccountController', 'getUsers'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/users' => [
        'handler' => ['Admin\AccountController', 'saveUser'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/users/delete' => [
        'handler' => ['Admin\AccountController', 'deleteUser'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],
    'POST /admin/api/users/bulk-delete' => [
        'handler' => ['Admin\AccountController', 'bulkDeleteUsers'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Audit Logs
    'GET /admin/audit-logs' => [
        'handler' => ['Admin\AuditController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/audit-logs' => [
        'handler' => ['Admin\AuditController', 'getLogs'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/audit-logs/detail' => [
        'handler' => ['Admin\AuditController', 'getAuditLogDetail'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/audit-logs/clear' => [
        'handler' => ['Admin\AuditController', 'clearLogs'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    'GET /admin/api/audit-logs/export' => [
        'handler' => ['Admin\AuditController', 'exportCSV'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    'GET /admin/api/audit-logs/download' => [
        'handler' => ['Admin\AuditController', 'downloadCSV'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    'GET /admin/api/audit-logs/export-progress' => [
        'handler' => ['Admin\AuditController', 'exportProgress'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    // Security Events
    'GET /admin/security-events' => [
        'handler' => ['Admin\SecurityEventController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'GET /admin/api/security-events' => [
        'handler' => ['Admin\SecurityEventController', 'getEvents'],
        'middleware' => ['Admin\AuthMiddleware']
    ],

    // Vote Toggle
    'GET /admin/api/vote-toggle' => [
        'handler' => ['Admin\ApiController', 'getVoteToggle'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/vote-toggle' => [
        'handler' => ['Admin\ApiController', 'toggleVote'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    'GET /admin/api/login-toggle' => [
        'handler' => ['Admin\ApiController', 'getLoginToggle'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/login-toggle' => [
        'handler' => ['Admin\ApiController', 'toggleLogin'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    'GET /admin/api/toggle-states' => [
        'handler' => ['Admin\ApiController', 'getToggleStates'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/api/toggle-state' => [
        'handler' => ['Admin\ApiController', 'toggleState'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Maintenance Mode
    'GET /admin/maintenance' => [
        'handler' => ['Admin\MaintenanceController', 'index'],
        'middleware' => ['Admin\AuthMiddleware']
    ],
    'POST /admin/maintenance/toggle' => [
        'handler' => ['Admin\MaintenanceController', 'toggle'],
        'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
    ],

    // Show Results

    'POST /admin/verify-password' => [
    'handler' => ['Admin\AuthController', 'verifyPassword'],
    'middleware' => ['Admin\AuthMiddleware', 'CsrfMiddleware']
],
];
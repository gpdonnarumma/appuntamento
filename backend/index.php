<?php
/**
 * Music School Scheduler - Backend API
 * Welcome Page
 */

header('Content-Type: application/json');

$response = [
    'success' => true,
    'name' => 'Music School Scheduler API',
    'version' => '1.0.0',
    'description' => 'Backend API per la gestione degli orari di una scuola di musica',
    'endpoints' => [
        'authentication' => [
            'POST /api/auth/register.php' => 'Registrazione utente',
            'POST /api/auth/login.php' => 'Login utente'
        ],
        'users' => [
            'GET /api/users/read.php' => 'Ottieni utenti',
            'PUT /api/users/update.php' => 'Aggiorna utente',
            'DELETE /api/users/delete.php' => 'Elimina utente'
        ],
        'schools' => [
            'POST /api/schools/create.php' => 'Crea scuola (admin)',
            'GET /api/schools/read.php' => 'Ottieni scuole',
            'PUT /api/schools/update.php' => 'Aggiorna scuola',
            'DELETE /api/schools/delete.php' => 'Elimina scuola'
        ],
        'courses' => [
            'POST /api/courses/create.php' => 'Crea corso (teacher)',
            'GET /api/courses/read.php' => 'Ottieni corsi',
            'PUT /api/courses/update.php' => 'Aggiorna corso',
            'DELETE /api/courses/delete.php' => 'Elimina corso'
        ],
        'lessons' => [
            'POST /api/lessons/create.php' => 'Crea lezione (teacher/admin)',
            'GET /api/lessons/read.php' => 'Ottieni lezioni',
            'PUT /api/lessons/update.php' => 'Aggiorna lezione',
            'DELETE /api/lessons/delete.php' => 'Cancella lezione'
        ],
        'enrollments' => [
            'POST /api/enrollments/request.php' => 'Richiesta iscrizione (student)',
            'GET /api/enrollments/read.php' => 'Ottieni richieste',
            'POST /api/enrollments/approve.php' => 'Approva richiesta (teacher)',
            'POST /api/enrollments/reject.php' => 'Rifiuta richiesta (teacher)'
        ],
        'teacher_requests' => [
            'POST /api/teacher_requests/request.php' => 'Richiesta scuola (teacher)',
            'GET /api/teacher_requests/read.php' => 'Ottieni richieste',
            'POST /api/teacher_requests/approve.php' => 'Approva richiesta (admin)',
            'POST /api/teacher_requests/reject.php' => 'Rifiuta richiesta (admin)'
        ],
        'notifications' => [
            'GET /api/notifications/read.php' => 'Ottieni notifiche',
            'POST /api/notifications/mark_read.php' => 'Marca come letta'
        ]
    ],
    'database' => [
        'type' => 'SQLite',
        'auto_init' => true,
        'location' => 'database/music_school.db'
    ],
    'documentation' => 'Vedi README.md per la documentazione completa'
];

echo json_encode($response, JSON_PRETTY_PRINT);

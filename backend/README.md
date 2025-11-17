# Music School Scheduler - Backend API

Backend PHP completo per la gestione degli orari di una scuola di musica.

## Caratteristiche

- **Database**: SQLite
- **Autenticazione**: JWT Token-based
- **API RESTful**: Formato JSON
- **3 Tipi di Utenti**: Amministratore (Scuola), Docente, Studente

## Struttura del Progetto

```
backend/
├── api/
│   ├── auth/              # Registrazione e Login
│   ├── users/             # Gestione utenti
│   ├── schools/           # Gestione scuole
│   ├── courses/           # Gestione corsi
│   ├── lessons/           # Gestione lezioni
│   ├── enrollments/       # Richieste iscrizione studente->corso
│   ├── teacher_requests/  # Richieste docente->scuola
│   └── notifications/     # Sistema notifiche
├── config/
│   └── database.php       # Configurazione database
├── models/                # Modelli dati
├── utils/                 # Helper e utility
└── database/
    ├── schema.sql         # Schema database
    └── music_school.db    # Database SQLite (auto-generato)
```

## Installazione

1. **Requisiti**:
   - PHP >= 7.4
   - SQLite3
   - Apache/Nginx (opzionale)

2. **Setup**:
   ```bash
   cd backend
   php -S localhost:8000
   ```

3. Il database verrà creato automaticamente al primo accesso.

## Endpoints API

### Authentication

#### Registrazione
```http
POST /api/auth/register.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "user_type": "teacher|student|admin",
  "first_name": "Mario",
  "last_name": "Rossi",
  "birth_date": "1990-01-15",
  "profile_photo": "url_foto" (opzionale)
}
```

#### Login
```http
POST /api/auth/login.php
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {...},
    "token": "jwt_token_here"
  }
}
```

### Users

#### Get User
```http
GET /api/users/read.php?id=1
GET /api/users/read.php?unique_id=ABC123
GET /api/users/read.php?type=teacher&search=mario
Authorization: Bearer {token}
```

#### Update User
```http
PUT /api/users/update.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1,
  "first_name": "Mario",
  "last_name": "Rossi",
  "preferences": {
    "notify_free_slots": 1,
    "notify_before_lesson": 1
  }
}
```

### Schools

#### Create School (solo Admin)
```http
POST /api/schools/create.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "school_name": "Scuola di Musica Roma",
  "city": "Roma"
}
```

#### Get Schools
```http
GET /api/schools/read.php?id=1
GET /api/schools/read.php?unique_id=SC1234ABCD
GET /api/schools/read.php?search=roma
Authorization: Bearer {token}
```

### Courses

#### Create Course (solo Teacher)
```http
POST /api/courses/create.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_name": "Pianoforte",
  "description": "Corso di pianoforte per principianti"
}
```

#### Get Courses
```http
GET /api/courses/read.php?id=1
GET /api/courses/read.php?teacher_id=5
GET /api/courses/read.php?student_id=10
GET /api/courses/read.php?teacher_unique_id=ABC123
GET /api/courses/read.php?instruments=true
Authorization: Bearer {token}
```

### Lessons

#### Create Lesson (Teacher/Admin)
```http
POST /api/lessons/create.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_id": 1,
  "student_id": 5,
  "lesson_date": "2024-12-20",
  "start_time": "10:00",
  "end_time": "11:00",
  "classroom": "Aula 1",
  "objectives": "Esercizi scale",
  "private_notes": "Note solo per docente",
  "is_recurring": 1,
  "recurrence_pattern": "weekly",
  "skip_notification": 0
}
```

#### Get Lessons
```http
GET /api/lessons/read.php?id=1
GET /api/lessons/read.php?teacher_id=5
GET /api/lessons/read.php?student_id=10&date_from=2024-01-01&date_to=2024-12-31
GET /api/lessons/read.php?student_id=10&next=true (prossima lezione)
GET /api/lessons/read.php?student_id=10&history=true (cronologia)
Authorization: Bearer {token}
```

#### Update Lesson
```http
PUT /api/lessons/update.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1,
  "lesson_date": "2024-12-21",
  "start_time": "11:00",
  "objectives": "Nuovi obiettivi",
  "update_recurring": true,
  "skip_notification": 0
}
```

#### Cancel Lesson
```http
DELETE /api/lessons/delete.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "id": 1,
  "delete_recurring": false,
  "skip_notification": false,
  "no_assign": 0
}
```

### Enrollment Requests (Studente -> Corso)

#### Send Request
```http
POST /api/enrollments/request.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_id": 1
}
```

#### Get Requests (Teacher)
```http
GET /api/enrollments/read.php?teacher_id=5
GET /api/enrollments/read.php?teacher_id=5&pending=true
Authorization: Bearer {token}
```

#### Approve Request
```http
POST /api/enrollments/approve.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "request_id": 1
}
```

#### Reject Request
```http
POST /api/enrollments/reject.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "request_id": 1
}
```

### Teacher-School Requests (Docente -> Scuola)

#### Send Request
```http
POST /api/teacher_requests/request.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "school_id": 1
}
```

#### Get Requests (Admin)
```http
GET /api/teacher_requests/read.php?school_id=1
GET /api/teacher_requests/read.php?school_id=1&pending=true
GET /api/teacher_requests/read.php?teacher_id=5
Authorization: Bearer {token}
```

#### Approve Request
```http
POST /api/teacher_requests/approve.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "request_id": 1
}
```

### Notifications

#### Get Notifications
```http
GET /api/notifications/read.php
GET /api/notifications/read.php?unread=true
GET /api/notifications/read.php?limit=20
Authorization: Bearer {token}
```

#### Mark as Read
```http
POST /api/notifications/mark_read.php
Authorization: Bearer {token}
Content-Type: application/json

{
  "notification_id": 1
}

// oppure marca tutte come lette
{
  "mark_all": true
}
```

## Workflow Applicazione

### Docente
1. Registrazione come "teacher"
2. Crea i propri corsi
3. Riceve richieste iscrizione dagli studenti
4. Approva/Rifiuta studenti
5. Prenota lezioni per gli studenti
6. Gestisce calendario lezioni (crea, modifica, cancella)
7. Può richiedere aggiunta a scuole

### Studente
1. Registrazione come "student"
2. Cerca docenti tramite unique_id
3. Invia richieste iscrizione ai corsi
4. Visualizza calendario lezioni
5. Visualizza cronologia lezioni
6. Gestisce preferenze notifiche

### Amministratore (Scuola)
1. Registrazione come "admin" (crea scuola)
2. Riceve richieste docenti
3. Approva/Rifiuta docenti
4. Visualizza lista studenti e docenti della scuola
5. Visualizza tutte le lezioni programmate
6. Può modificare dati studenti

## Sistema Notifiche

Il sistema invia automaticamente:
- Email per richieste iscrizione
- Email per approvazioni/rifiuti
- Email per modifiche lezioni
- Email per cancellazioni lezioni
- Email per slot liberi (se abilitato)
- Promemoria 1 ora prima della lezione (se abilitato)

## Sicurezza

- Password hashate con bcrypt
- Autenticazione JWT
- Validazione input sanitized
- SQL Injection protection (PDO prepared statements)
- Permission checks su ogni endpoint

## Note Implementazione

- ID Univoci:
  - Utenti: 8 caratteri alfanumerici (es. A7X9K2M5)
  - Scuole: SC + 4 cifre + 4 alfanumerici (es. SC1234A7X9)

- Lezioni ricorrenti:
  - Weekly: ripete ogni settimana
  - Monthly: ripete ogni mese
  - Genera automaticamente 52 occorrenze (1 anno)

- Soft delete: Le entità vengono marcate come `is_active = 0` invece di essere eliminate

## Testing

Usa Postman, curl o qualsiasi client HTTP per testare le API.

Esempio con curl:
```bash
# Register
curl -X POST http://localhost:8000/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@test.com","password":"test123","user_type":"teacher","first_name":"Mario","last_name":"Rossi","birth_date":"1990-01-15"}'

# Login
curl -X POST http://localhost:8000/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"teacher@test.com","password":"test123"}'
```

## Troubleshooting

- Se il database non si crea automaticamente, verifica i permessi della directory `backend/database/`
- Per problemi CORS, verifica che `enableCORS()` sia chiamato in ogni endpoint
- Per email, configura un servizio SMTP in `utils/email.php`

## Sviluppi Futuri

- Integrazione servizio email (SendGrid, Mailgun, ecc.)
- Upload immagini profilo
- Export calendario (iCal)
- Statistiche e report
- Pagamenti lezioni
- Chat docente-studente

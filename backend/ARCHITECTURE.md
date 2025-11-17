# Architettura Backend - Music School Scheduler

## Panoramica

Backend REST API sviluppato in PHP con database SQLite per la gestione completa di una scuola di musica.

## Stack Tecnologico

- **Linguaggio**: PHP 7.4+
- **Database**: SQLite 3
- **Autenticazione**: JWT (JSON Web Tokens)
- **Architettura**: REST API
- **Pattern**: MVC (Model-View-Controller) semplificato

## Struttura Progetto

```
backend/
├── api/                          # REST API Endpoints
│   ├── auth/                     # Autenticazione
│   │   ├── register.php          # Registrazione utenti
│   │   └── login.php             # Login
│   ├── users/                    # Gestione utenti
│   │   ├── read.php              # GET utenti
│   │   ├── update.php            # PUT aggiorna utente
│   │   └── delete.php            # DELETE utente
│   ├── schools/                  # Gestione scuole
│   │   ├── create.php            # POST crea scuola
│   │   ├── read.php              # GET scuole
│   │   ├── update.php            # PUT aggiorna scuola
│   │   └── delete.php            # DELETE scuola
│   ├── courses/                  # Gestione corsi
│   │   ├── create.php            # POST crea corso
│   │   ├── read.php              # GET corsi
│   │   ├── update.php            # PUT aggiorna corso
│   │   └── delete.php            # DELETE corso
│   ├── lessons/                  # Gestione lezioni
│   │   ├── create.php            # POST crea lezione
│   │   ├── read.php              # GET lezioni
│   │   ├── update.php            # PUT aggiorna lezione
│   │   └── delete.php            # DELETE/cancella lezione
│   ├── enrollments/              # Richieste iscrizione studente->corso
│   │   ├── request.php           # POST invia richiesta
│   │   ├── read.php              # GET richieste
│   │   ├── approve.php           # POST approva
│   │   └── reject.php            # POST rifiuta
│   ├── teacher_requests/         # Richieste docente->scuola
│   │   ├── request.php           # POST invia richiesta
│   │   ├── read.php              # GET richieste
│   │   ├── approve.php           # POST approva
│   │   └── reject.php            # POST rifiuta
│   └── notifications/            # Sistema notifiche
│       ├── read.php              # GET notifiche
│       └── mark_read.php         # POST marca letta
├── config/
│   └── database.php              # Configurazione DB (Singleton pattern)
├── models/                       # Business Logic Layer
│   ├── User.php                  # Modello utenti
│   ├── School.php                # Modello scuole
│   ├── Course.php                # Modello corsi
│   ├── Lesson.php                # Modello lezioni
│   └── Enrollment.php            # Modello iscrizioni/richieste
├── utils/                        # Utility Functions
│   ├── helpers.php               # Funzioni helper generiche
│   └── email.php                 # Servizio email/notifiche
├── database/
│   ├── schema.sql                # Schema database SQL
│   └── music_school.db           # Database SQLite (auto-generato)
├── .htaccess                     # Configurazione Apache
├── index.php                     # Welcome page
├── test_setup.php                # Script test installazione
├── README.md                     # Documentazione API
├── INSTALL.md                    # Guida installazione
└── ARCHITECTURE.md               # Questo file
```

## Database Schema

### Entità Principali

#### users
- Tabella base per tutti i tipi di utente (admin, teacher, student)
- Campi: id, email, password_hash, user_type, first_name, last_name, birth_date, profile_photo, unique_id
- Unique ID: 8 caratteri alfanumerici generati automaticamente

#### schools
- Scuole gestite da amministratori
- Campi: id, admin_id (FK users), school_name, city, unique_id
- Unique ID: SC + 4 cifre + 4 alfanumerici

#### courses
- Corsi creati dai docenti
- Campi: id, teacher_id (FK users), course_name, description

#### lessons
- Lezioni programmate
- Campi: id, course_id, student_id, teacher_id, lesson_date, start_time, end_time, classroom, private_notes, objectives, is_recurring, recurrence_pattern, parent_lesson_id, status
- Supporta lezioni ricorrenti (weekly/monthly)

#### course_enrollments
- Iscrizioni approvate studente->corso
- Many-to-Many: students <-> courses

#### enrollment_requests
- Richieste pendenti di iscrizione
- Stati: pending, approved, rejected

#### teacher_schools
- Relazione docenti-scuole
- Many-to-Many: teachers <-> schools

#### teacher_school_requests
- Richieste pendenti docente->scuola
- Stati: pending, approved, rejected

#### student_preferences
- Preferenze notifiche studente
- notify_free_slots, notify_before_lesson

#### notifications
- Sistema notifiche centralizzato
- Tipi: enrollment_request, lesson_created, lesson_modified, ecc.

### Relazioni

```
users (admin) 1----* schools
users (teacher) 1----* courses
users (teacher) *----* schools (via teacher_schools)
users (student) *----* courses (via course_enrollments)
courses 1----* lessons
users (student) 1----* lessons
users (teacher) 1----* lessons
lessons 1----* lessons (ricorrenza via parent_lesson_id)
```

## Pattern e Pratiche

### 1. Singleton Pattern (Database)
```php
Database::getInstance()->getConnection()
```
- Una sola istanza di connessione DB
- Inizializzazione automatica schema

### 2. Repository Pattern (Models)
- Ogni modello gestisce operazioni CRUD sulla propria entità
- Astrae la logica SQL dai controller (API endpoints)

### 3. Helper Functions
- Funzioni riutilizzabili per validazione, sanitizzazione, JWT
- Centralizzate in `utils/helpers.php`

### 4. Service Layer (Email)
- `EmailService` gestisce notifiche e invio email
- Facilmente estendibile con provider esterni (SendGrid, Mailgun)

### 5. REST API Conventions
- GET: Lettura dati
- POST: Creazione nuove risorse
- PUT: Aggiornamento risorse esistenti
- DELETE: Cancellazione (soft delete)

### 6. Autenticazione JWT
```
Header: Authorization: Bearer {token}
Token payload: {user_id, user_type, exp}
```

### 7. Validazione e Sicurezza
- Sanitizzazione input con `htmlspecialchars()` e `strip_tags()`
- Prepared statements PDO (protezione SQL Injection)
- Password hashing con bcrypt
- Permission checks su ogni endpoint

### 8. CORS Support
- Headers CORS abilitati tramite `enableCORS()`
- Gestione preflight OPTIONS requests

### 9. Error Handling
```php
try {
    // logic
} catch (Exception $e) {
    sendError('Message', 500);
}
```

### 10. Response Format
```json
{
  "success": true|false,
  "message": "Human readable message",
  "data": {...}  // optional
}
```

## Flussi Principali

### 1. Registrazione e Login

```
Client -> POST /api/auth/register.php
       -> Validazione input
       -> Generazione unique_id
       -> Hash password (bcrypt)
       -> Inserimento DB
       -> Creazione preferences (se student)
       <- Risposta con user + JWT token

Client -> POST /api/auth/login.php
       -> Validazione credenziali
       -> Verifica password hash
       <- Risposta con user + JWT token
```

### 2. Richiesta Iscrizione Studente

```
Student -> POST /api/enrollments/request.php + JWT
        -> Verifica student è loggato
        -> Controllo corso esiste
        -> Creazione enrollment_request (pending)
        -> Notifica via email a teacher
        <- Conferma richiesta inviata

Teacher <- Email notifica
Teacher -> GET /api/enrollments/read.php?teacher_id=X&pending=true
        <- Lista richieste pendenti

Teacher -> POST /api/enrollments/approve.php {request_id}
        -> Aggiornamento stato -> approved
        -> Creazione course_enrollment
        -> Notifica via email a student
        <- Conferma approvazione

Student <- Email approvazione
Student -> GET /api/courses/read.php?student_id=X
        <- Lista corsi iscritti
```

### 3. Prenotazione Lezione

```
Teacher -> POST /api/lessons/create.php + JWT
        -> Verifica teacher è owner del corso
        -> Verifica student è iscritto
        -> Controllo conflitti orario
        -> Creazione lezione
        -> (Se ricorrente) Creazione 52 occorrenze
        -> Notifica via email a student
        <- Conferma lezione creata

Student <- Email nuova lezione
Student -> GET /api/lessons/read.php?student_id=X
        <- Calendario lezioni

Student -> GET /api/lessons/read.php?student_id=X&next=true
        <- Prossima lezione
```

### 4. Modifica Lezione

```
Teacher -> PUT /api/lessons/update.php + JWT
        -> Verifica ownership
        -> Controllo conflitti se cambia orario
        -> Aggiornamento lezione
        -> (Se update_recurring) Aggiorna tutte le ricorrenze
        -> Notifica via email a student
        <- Conferma modifica

Student <- Email modifica lezione
```

### 5. Cancellazione Lezione

```
Teacher -> DELETE /api/lessons/delete.php + JWT
        -> Verifica ownership
        -> Cambio status -> cancelled
        -> (Se delete_recurring) Cancella tutte le ricorrenze
        -> Notifica studente (se !skip_notification)
        -> (Se !no_assign) Notifica altri studenti slot libero
        <- Conferma cancellazione

Student <- Email cancellazione
Other Students <- Email slot libero (se preferences.notify_free_slots = 1)
```

## Sicurezza

### Livelli di Protezione

1. **Input Validation**
   - Validazione email, date, time
   - Required fields check
   - Type checking

2. **Input Sanitization**
   - `htmlspecialchars()` + `strip_tags()` + `trim()`
   - Protezione XSS

3. **SQL Injection Prevention**
   - PDO Prepared Statements
   - Parametrizzazione query

4. **Authentication**
   - JWT token-based
   - Token expiration (7 giorni)
   - Signature verification

5. **Authorization**
   - Role-based access control
   - Resource ownership checks
   - Permission verificata su ogni endpoint

6. **Password Security**
   - Bcrypt hashing (PASSWORD_BCRYPT)
   - Never stored in plain text
   - Minimum 6 characters

7. **CORS**
   - Headers configurabili
   - Preflight request support

### Permission Matrix

| Risorsa | Admin | Teacher | Student |
|---------|-------|---------|---------|
| Users (self) | RUD | RUD | RUD |
| Users (others) | RUD | R (students only) | - |
| Schools | CRUD | R | R |
| Courses | R | CRUD (own) | R (enrolled) |
| Lessons | CRUD | CRUD (own courses) | R (own) |
| Enrollments (approve) | - | CRUD | - |
| Enrollments (request) | - | - | C |
| Teacher Requests (approve) | CRUD (own school) | - | - |
| Teacher Requests (request) | - | C | - |
| Notifications | R (own) | R (own) | R (own) |

## Scalabilità e Performance

### Ottimizzazioni Implementate

1. **Database Indexes**
   - Index su colonne frequent queries (email, unique_id, user_type, ecc.)
   - Foreign keys indicizzate

2. **Query Optimization**
   - JOIN solo quando necessario
   - LIMIT su liste lunghe
   - SELECT solo campi necessari

3. **Singleton Pattern**
   - Una connessione DB per request

4. **Soft Delete**
   - Evita DELETE pesanti
   - Flag `is_active = 0`

### Considerazioni Future

1. **Caching**
   - Redis/Memcached per query frequenti
   - Cache invalidation su update

2. **Database Sharding**
   - Se molte scuole, considera sharding per school_id

3. **CDN**
   - Per immagini profilo e file statici

4. **Background Jobs**
   - Cron per invio email promemoria
   - Queue system (RabbitMQ/Redis) per notifiche

5. **Rate Limiting**
   - Protezione API abuse
   - Token bucket algorithm

6. **Monitoring**
   - Logging strutturato
   - Error tracking (Sentry)
   - Performance monitoring (New Relic)

## Testing

### Unit Testing (da implementare)
```php
// PHPUnit
tests/
├── models/
│   ├── UserTest.php
│   ├── LessonTest.php
│   └── ...
└── api/
    ├── AuthTest.php
    └── ...
```

### Integration Testing
- Test API endpoints end-to-end
- Test workflow completi (registrazione -> iscrizione -> lezione)

### Load Testing
- Apache Bench / JMeter per stress test
- Verificare performance con 1000+ utenti concorrenti

## Deployment

### Development
```bash
php -S localhost:8000
```

### Production

1. **Apache/Nginx**
   - mod_rewrite / try_files
   - HTTPS obbligatorio
   - PHP-FPM per performance

2. **Environment Variables**
   - JWT_SECRET_KEY
   - DB_PATH
   - SMTP_CONFIG

3. **Backup Strategy**
   - Database backup giornaliero
   - Retention 30 giorni
   - Off-site backup

4. **Monitoring**
   - Uptime monitoring
   - Error logging
   - Performance metrics

## Estensioni Future

1. **File Upload**
   - Foto profilo
   - Materiale didattico
   - S3/Cloud Storage

2. **Payments**
   - Stripe integration
   - Fatturazione lezioni

3. **Calendar Export**
   - iCal/Google Calendar sync

4. **Messaging**
   - Chat docente-studente
   - WebSocket real-time

5. **Statistics**
   - Dashboard analytics
   - Report presenze
   - Performance studenti

6. **Multi-language**
   - i18n support
   - Localizzazione

7. **Mobile App**
   - Same API backend
   - Push notifications

## Riferimenti

- **PHP**: https://www.php.net/
- **SQLite**: https://www.sqlite.org/
- **JWT**: https://jwt.io/
- **REST API Best Practices**: https://restfulapi.net/

---

Documento redatto per Music School Scheduler v1.0

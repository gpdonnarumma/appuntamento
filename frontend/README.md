# Music School Scheduler - Frontend

Frontend PHP per la gestione completa di una scuola di musica con interfaccia user-friendly e design responsive.

## Caratteristiche

- **3 Ruoli Utente** con interfacce personalizzate
- **Design Responsive** - funziona su desktop, tablet e mobile
- **Colori Personalizzati** per ogni ruolo
- **Interfaccia Intuitiva** - facile da usare
- **Sistema di Notifiche** integrato
- **Sessioni Sicure** con JWT backend

## Colori per Ruolo

- 🔵 **Amministratore (Scuola)**: Blu Navy/Azzurro (#2C3E50, #3498DB)
- 🟢 **Docente**: Verde/Teal (#27AE60, #16A085)
- 🟠 **Studente**: Arancione/Coral (#E67E22, #F39C12)

## Struttura

```
frontend/
├── index.php                  # Landing page e login
├── register.php               # Registrazione utente
├── logout.php                 # Logout
├── config.php                 # Configurazione e sessioni
├── api_client.php             # Client per API backend
├── assets/
│   ├── css/
│   │   ├── common.css         # Stili comuni
│   │   ├── admin.css          # Stili amministratore
│   │   ├── teacher.css        # Stili docente
│   │   └── student.css        # Stili studente
│   └── js/
│       └── common.js          # JavaScript comune
├── includes/
│   ├── header.php             # Header comune
│   └── footer.php             # Footer comune
├── admin/                     # Area amministratore
│   ├── index.php              # Dashboard admin
│   ├── school.php             # Gestione scuola
│   ├── teachers.php           # Lista docenti
│   ├── students.php           # Lista studenti
│   ├── lessons.php            # Calendario lezioni
│   └── requests.php           # Richieste docenti
├── teacher/                   # Area docente
│   ├── index.php              # Dashboard docente
│   ├── courses.php            # Gestione corsi
│   ├── lessons.php            # Gestione lezioni
│   ├── students.php           # Studenti iscritti
│   ├── requests.php           # Richieste iscrizione
│   ├── schools.php            # Scuole
│   └── profile.php            # Profilo docente
└── student/                   # Area studente
    ├── index.php              # Dashboard studente
    ├── courses.php            # Corsi iscritti
    ├── search.php             # Cerca docente
    ├── calendar.php           # Calendario lezioni
    ├── notifications.php      # Notifiche
    └── profile.php            # Profilo studente
```

## Installazione

### Requisiti

- PHP >= 7.4
- Backend API in esecuzione (vedi backend/README.md)
- Webserver (Apache/Nginx) o PHP built-in server

### Setup

1. **Configura URL Backend**

   Modifica `config.php`:
   ```php
   define('API_BASE_URL', 'http://localhost:8000/api');
   ```

2. **Avvia il Server**

   Opzione A - PHP Built-in Server:
   ```bash
   cd frontend
   php -S localhost:3000
   ```

   Opzione B - Apache:
   ```bash
   # Copia in document root
   sudo cp -r frontend /var/www/html/
   ```

3. **Accedi**

   Apri il browser: http://localhost:3000

## Funzionalità per Ruolo

### 👨‍💼 Amministratore (Scuola)

**Dashboard**
- Statistiche scuola (docenti, studenti, lezioni)
- ID univoco scuola (copiabile)
- Richieste docenti pendenti
- Quick actions

**Gestione Scuola**
- Modifica dati scuola (nome, città)
- Visualizza ID univoco

**Docenti**
- Lista docenti della scuola
- Approva/Rifiuta richieste
- Rimuovi docente

**Studenti**
- Lista studenti iscritti
- Visualizza dettagli studente
- Modifica dati studente

**Calendario Lezioni**
- Visualizza tutte le lezioni
- Filtra per docente/studente
- Vista calendario completo

### 👨‍🏫 Docente

**Dashboard**
- Statistiche (corsi, studenti, lezioni)
- ID univoco docente (copiabile)
- Richieste iscrizione pendenti
- Lista corsi

**Gestione Corsi**
- Crea nuovo corso
- Modifica/Elimina corso
- Lista studenti iscritti
- Strumenti musicali predefiniti

**Gestione Lezioni**
- Calendario lezioni
- Crea lezione (singola/ricorrente)
  - Weekly (ogni settimana)
  - Monthly (ogni mese)
- Modifica lezione
  - Modifica singola lezione
  - Modifica tutte le ricorrenze
- Cancella lezione
  - Opzione "Non assegnare"
  - Cancella ricorrenze
- Note personali (solo docente)
- Obiettivi (visibili a studente)
- Controllo conflitti orario

**Richieste Iscrizione**
- Lista richieste pendenti
- Approva/Rifiuta studenti
- Notifica automatica via email

**Studenti**
- Lista studenti per corso
- Visualizza dettagli studente
- Cronologia lezioni

**Scuole**
- Cerca scuole
- Invia richiesta adesione
- Visualizza scuole associate

**Profilo**
- Visualizza/Modifica anagrafica
- Upload foto profilo
- Copia ID univoco

### 🎓 Studente

**Dashboard**
- Banner prossima lezione
- Statistiche (corsi, lezioni, notifiche)
- ID univoco studente (copiabile)
- Lista corsi iscritti
- Prossime lezioni

**Cerca Docente**
- Ricerca per ID docente
- Visualizza corsi del docente
- Invia richiesta iscrizione

**I Miei Corsi**
- Lista corsi iscritti
- Dettagli corso
- Info docente

**Calendario**
- Calendario lezioni future
- Visualizza obiettivi lezione
- Cronologia lezioni passate

**Notifiche**
- Lista notifiche
- Segna come lette
- Badge notifiche non lette

**Profilo**
- Visualizza anagrafica (non modificabile)
- Foto profilo
- Preferenze notifiche:
  - ✅ Avvisami slot liberi
  - ✅ Promemoria 1 ora prima
- Copia ID univoco

## Sistema di Notifiche

### Tipi di Notifiche

**Studente riceve:**
- Nuova lezione programmata
- Lezione modificata
- Lezione cancellata
- Slot libero disponibile (se abilitato)
- Promemoria 1 ora prima (se abilitato)
- Iscrizione approvata
- Iscrizione rifiutata

**Docente riceve:**
- Nuova richiesta iscrizione studente
- Richiesta scuola approvata

**Admin riceve:**
- Nuova richiesta docente
- Richiesta modifica dati studente (se presente admin)

### Gestione Notifiche

```php
// Segna singola notifica come letta
POST /student/notifications.php
{
  "action": "mark_read",
  "notification_id": 123
}

// Segna tutte come lette
POST /student/notifications.php
{
  "action": "mark_all_read"
}
```

## Workflow Applicativi

### 1. Registrazione e Login

```
1. Utente apre index.php
2. Clicca "Registrati"
3. Sceglie ruolo (Admin/Teacher/Student)
4. Compila form registrazione
   - Admin: anche dati scuola
5. Sistema crea utente + genera ID univoco
6. Redirect a dashboard appropriata
```

### 2. Iscrizione Studente a Corso

```
1. Studente riceve ID docente
2. Va in "Cerca Docente"
3. Inserisce ID docente
4. Visualizza corsi disponibili
5. Clicca "Invia Richiesta"
6. Sistema crea enrollment_request
7. Docente riceve notifica email
8. Docente va in "Richieste"
9. Approva/Rifiuta richiesta
10. Studente riceve notifica email
11. Se approvato: corso appare in "I Miei Corsi"
```

### 3. Prenotazione Lezione

```
1. Docente va in "Lezioni"
2. Clicca "Crea Lezione"
3. Seleziona:
   - Corso
   - Studente (tra quelli iscritti)
   - Data e ora
   - Aula (opzionale)
   - Obiettivi (visibili a studente)
   - Note personali (solo docente)
   - Ricorrenza (opzionale):
     * Weekly: genera 52 lezioni
     * Monthly: genera 52 lezioni
4. Sistema verifica conflitti orario
5. Se OK: crea lezione(i)
6. Studente riceve notifica email
7. Lezione appare in calendario studente
```

### 4. Modifica Lezione

```
1. Docente va in "Lezioni"
2. Seleziona lezione da modificare
3. Modifica data/ora/aula/note/obiettivi
4. Sceglie se aggiornare ricorrenze
5. Sistema verifica conflitti
6. Salva modifiche
7. Studente riceve notifica email
```

### 5. Cancellazione Lezione

```
1. Docente va in "Lezioni"
2. Seleziona lezione da cancellare
3. Opzioni:
   - Cancella solo questa
   - Cancella tutte le ricorrenze
   - "Non assegnare" (non notifica altri)
4. Sistema cancella lezione
5. Se "Assegna":
   - Notifica studenti con "notify_free_slots" attivo
6. Altrimenti solo studente interessato
```

### 6. Docente si unisce a Scuola

```
1. Docente riceve ID scuola
2. Va in "Scuole"
3. Cerca scuola per ID o nome
4. Clicca "Invia Richiesta"
5. Admin riceve notifica email
6. Admin va in "Richieste"
7. Visualizza anagrafica docente
8. Approva/Rifiuta
9. Docente riceve notifica email
10. Se approvato: scuola appare in lista
```

## API Integration

Il frontend comunica con il backend tramite `api_client.php`:

### Esempio Chiamata API

```php
// Login
$result = apiLogin($email, $password);
if ($result['success']) {
    $_SESSION['user'] = $result['data']['user'];
    $_SESSION['token'] = $result['data']['token'];
}

// Get corsi
$courses = apiGetCourse(null, $teacherId);

// Create lezione
$result = apiCreateLesson($courseId, $studentId, $date, $start, $end, [
    'classroom' => 'Aula 1',
    'objectives' => 'Scale maggiori',
    'is_recurring' => 1,
    'recurrence_pattern' => 'weekly'
]);
```

## Sessioni e Autenticazione

### Gestione Sessione

```php
// Login salva in sessione
$_SESSION['user'] = $userData;
$_SESSION['token'] = $jwtToken;

// Ogni pagina verifica
requireLogin();        // Solo logged in
requireRole('teacher'); // Solo teacher
```

### Token JWT

- Salvato in sessione
- Inviato in ogni richiesta API
- Header: `Authorization: Bearer {token}`
- Scadenza: 7 giorni

## Personalizzazione Design

### Cambiare Colori Ruolo

Modifica `assets/css/admin.css`, `teacher.css`, `student.css`:

```css
:root {
    --primary-color: #YOUR_COLOR;
    --secondary-color: #YOUR_COLOR;
}
```

### Aggiungere Nuove Pagine

1. Crea file in cartella appropriata
2. Includi header: `include __DIR__ . '/../includes/header.php';`
3. Usa helper functions: `requireRole()`, `apiRequest()`
4. Includi footer: `include __DIR__ . '/../includes/footer.php';`

## Helper Functions

### Config.php

```php
isLoggedIn()                    // Check if user logged in
getCurrentUser()                // Get current user data
getToken()                      // Get JWT token
getUserRole()                   // Get user role
hasRole($role)                  // Check if has role
requireLogin()                  // Redirect if not logged in
requireRole($role)              // Redirect if wrong role
getRoleColor()                  // Get role primary color
getRoleName()                   // Get role name in Italian
formatDate($date)               // Format date (dd/mm/yyyy)
formatTime($time)               // Format time (HH:MM)
setSuccessMessage($msg)         // Set success flash message
setErrorMessage($msg)           // Set error flash message
```

### JavaScript (common.js)

```javascript
showLoading()                   // Show loading overlay
hideLoading()                   // Hide loading overlay
showAlert(msg, type)            // Show alert message
copyToClipboard(text)           // Copy text to clipboard
formatDate(dateString)          // Format date
formatTime(timeString)          // Format time
confirmAction(message)          // Confirm dialog
openModal(modalId)              // Open modal
closeModal(modalId)             // Close modal
validateForm(formId)            // Validate form
validateEmail(email)            // Validate email
```

## Responsive Design

Il frontend è completamente responsive:

- **Desktop** (>768px): Layout a colonne, menu orizzontale
- **Tablet** (768px): Layout adattivo
- **Mobile** (<768px): Layout verticale, menu stack

## Browser Support

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

## Sicurezza

### Implementata

- ✅ Sessioni PHP sicure
- ✅ CSRF protection (session-based)
- ✅ XSS prevention (htmlspecialchars)
- ✅ SQL Injection prevention (backend PDO)
- ✅ JWT token validation
- ✅ Role-based access control

### Best Practices

1. **Non committare credenziali**
2. **Usa HTTPS in produzione**
3. **Cambia secret key JWT**
4. **Abilita session security**:
   ```php
   ini_set('session.cookie_httponly', 1);
   ini_set('session.cookie_secure', 1);
   ini_set('session.use_strict_mode', 1);
   ```

## Troubleshooting

### Errore: "API Backend not available"
- Verifica che backend sia avviato
- Controlla `API_BASE_URL` in config.php

### Errore: "Session expired"
- Token JWT scaduto (7 giorni)
- Fai logout e re-login

### Pagina bianca
- Controlla error log PHP
- Verifica includes path corretti

### CSS non caricato
- Verifica path in header.php
- Controlla permessi file

## Testing

### Test Manuale

1. **Registrazione**
   - Test 3 ruoli
   - Verifica ID univoci generati
   - Verifica redirect corretto

2. **Login**
   - Credenziali corrette
   - Credenziali errate
   - Redirect basato su ruolo

3. **Workflow Completo**
   - Docente crea corso
   - Studente cerca e si iscrive
   - Docente approva
   - Docente crea lezione
   - Studente visualizza

## Deploy Produzione

1. **Setup Environment**
   ```bash
   # Apache
   sudo cp -r frontend /var/www/html/music-school
   sudo chown -R www-data:www-data /var/www/html/music-school
   ```

2. **Configura Apache**
   ```apache
   <VirtualHost *:80>
       ServerName musicschool.example.com
       DocumentRoot /var/www/html/music-school

       <Directory /var/www/html/music-school>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. **Enable HTTPS**
   ```bash
   sudo certbot --apache -d musicschool.example.com
   ```

4. **Production Config**
   ```php
   // config.php
   define('API_BASE_URL', 'https://api.musicschool.example.com/api');

   // Enable session security
   ini_set('session.cookie_secure', 1);
   ini_set('session.cookie_httponly', 1);
   ```

## Estensioni Future

- [ ] Upload foto profilo
- [ ] Export calendario (iCal)
- [ ] Chat docente-studente
- [ ] Pagamenti online
- [ ] App mobile (PWA)
- [ ] Notifiche push
- [ ] Video lezioni
- [ ] Materiale didattico

## Supporto

Per problemi o domande:
- Consulta backend/README.md per API
- Verifica logs PHP
- Controlla console browser (F12)

---

**Music School Scheduler Frontend v1.0**
Sviluppato con ❤️ per le scuole di musica

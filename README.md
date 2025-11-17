# 🎵 Music School Scheduler

Sistema completo di gestione orari per scuole di musica con interfaccia web user-friendly.

[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net)
[![SQLite](https://img.shields.io/badge/SQLite-3-green.svg)](https://sqlite.org)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 🌟 Caratteristiche Principali

- ✅ **3 Ruoli Utente**: Amministratore, Docente, Studente
- ✅ **Gestione Completa Corsi** con iscrizioni e approvazioni
- ✅ **Calendario Lezioni** con ricorrenze (settimanali/mensili)
- ✅ **Sistema Notifiche** via email integrato
- ✅ **ID Univoci** per facilitare le ricerche
- ✅ **Design Responsive** con colori personalizzati per ruolo
- ✅ **Backend REST API** completo in PHP + SQLite
- ✅ **Frontend PHP** moderno e intuitivo

## 🚀 Quick Start

### Prerequisiti

- PHP >= 7.4
- SQLite3
- curl (per API testing)

### Installazione Rapida

```bash
# 1. Clone repository
git clone https://github.com/gpdonnarumma/appuntamento.git
cd appuntamento

# 2. Avvia backend (terminale 1)
cd backend
php -S localhost:8000

# 3. Avvia frontend (terminale 2)
cd frontend
php -S localhost:3000

# 4. Apri browser
open http://localhost:3000
```

### Primo Utilizzo

1. Vai su http://localhost:3000
2. Clicca **"Registrati"**
3. Scegli un ruolo:
   - 🏫 **Amministratore** per gestire una scuola
   - 👨‍🏫 **Docente** per insegnare corsi
   - 🎓 **Studente** per iscriverti ai corsi
4. Compila il form e accedi alla tua dashboard!

## 📖 Documentazione

### Documentazione Completa

- 📘 [**SUMMARY.md**](SUMMARY.md) - Panoramica completa del progetto
- 📗 [**backend/README.md**](backend/README.md) - Documentazione API Backend
- 📕 [**frontend/README.md**](frontend/README.md) - Guida Frontend
- 📙 [**backend/ARCHITECTURE.md**](backend/ARCHITECTURE.md) - Architettura dettagliata
- 📓 [**backend/INSTALL.md**](backend/INSTALL.md) - Guida installazione

### Guide Rapide

- [Come testare l'applicazione](SUMMARY.md#-test-dellapplicazione)
- [Workflow principali](SUMMARY.md#-workflow-principali)
- [Deploy in produzione](SUMMARY.md#-deployment-produzione)

## 🏗️ Architettura

```
┌─────────────────────────────────────────────────┐
│              Frontend (PHP)                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐      │
│  │  Admin   │  │ Teacher  │  │ Student  │      │
│  └──────────┘  └──────────┘  └──────────┘      │
└─────────────────────────────────────────────────┘
                      │
                      │ HTTPS/JSON
                      ▼
┌─────────────────────────────────────────────────┐
│            Backend REST API (PHP)                │
│  ┌──────────────────────────────────────────┐  │
│  │  Auth  │ Users │ Courses │ Lessons │...  │  │
│  └──────────────────────────────────────────┘  │
│                     │                            │
│                     ▼                            │
│            ┌─────────────────┐                  │
│            │  SQLite DB      │                  │
│            │  12 Tabelle     │                  │
│            └─────────────────┘                  │
└─────────────────────────────────────────────────┘
```

## 👥 Ruoli e Funzionalità

### 🔵 Amministratore (Scuola)

- Gestisce la propria scuola
- Approva richieste docenti
- Visualizza tutti gli studenti e docenti
- Accesso completo al calendario lezioni
- ID univoco formato: **SC1234ABCD**

### 🟢 Docente

- Crea e gestisce corsi
- Prenota lezioni (singole o ricorrenti)
- Approva richieste iscrizione studenti
- Scrive note private e assegna obiettivi
- Si iscrive a scuole
- ID univoco formato: **ABC12345**

### 🟠 Studente

- Cerca docenti per ID
- Invia richieste iscrizione
- Visualizza calendario lezioni personalizzato
- Riceve notifiche configurabili
- Vede obiettivi delle lezioni
- ID univoco formato: **DEF67890**

## 🔄 Workflow Esempio

### Iscrizione Studente a Corso

```
1. 👨‍🏫 Docente condivide il suo ID univoco (es: ABC12345)
2. 🎓 Studente cerca il docente per ID
3. 🎓 Studente visualizza i corsi e invia richiesta
4. 👨‍🏫 Docente riceve notifica email
5. 👨‍🏫 Docente approva la richiesta
6. 🎓 Studente riceve conferma email
7. ✅ Studente è iscritto al corso!
```

### Prenotazione Lezione Ricorrente

```
1. 👨‍🏫 Docente crea lezione
2. 👨‍🏫 Seleziona "Ricorrenza: Weekly"
3. 🤖 Sistema crea 52 lezioni (1 anno)
4. 📧 Studente riceve notifica
5. 🎓 Studente visualizza tutte le lezioni in calendario
```

## 🛠️ Stack Tecnologico

### Backend
- **PHP 7.4+** - Linguaggio server-side
- **SQLite** - Database leggero e performante
- **JWT** - Autenticazione token-based
- **REST API** - Architettura API moderna

### Frontend
- **PHP** - Server-side rendering
- **HTML5/CSS3** - Markup e styling
- **JavaScript** - Interazioni client-side
- **Responsive Design** - Mobile-first approach

## 📊 Database Schema

12 tabelle principali:

- `users` - Utenti (tutti i ruoli)
- `schools` - Scuole
- `courses` - Corsi
- `lessons` - Lezioni (con ricorrenza)
- `course_enrollments` - Iscrizioni approvate
- `enrollment_requests` - Richieste pendenti
- `teacher_schools` - Docenti nelle scuole
- `teacher_school_requests` - Richieste docenti
- `student_preferences` - Preferenze studente
- `notifications` - Notifiche centralizzate
- `available_instruments` - Strumenti musicali
- `lesson_history` - Audit log

## 🔐 Sicurezza

- ✅ Password hashate con bcrypt
- ✅ JWT con expiration (7 giorni)
- ✅ SQL Injection prevention (PDO)
- ✅ XSS protection (htmlspecialchars)
- ✅ CSRF protection (session-based)
- ✅ Role-based access control
- ✅ Input validation e sanitization

## 🧪 Testing

### Test Rapido

```bash
# Test backend
cd backend
php test_setup.php

# Test API
curl http://localhost:8000

# Test registrazione
curl -X POST http://localhost:8000/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123","user_type":"teacher","first_name":"Mario","last_name":"Rossi","birth_date":"1990-01-01"}'
```

### Test Scenario Completo

Vedi [SUMMARY.md - Test dell'Applicazione](SUMMARY.md#-test-dellapplicazione)

## 📈 Statistiche Progetto

- **~10,000** righe di codice
- **31** API endpoints
- **42** file backend
- **17** file frontend
- **12** tabelle database
- **3** ruoli utente
- **100%** funzionale
- **100%** documentato

## 🚢 Deploy Produzione

### Quick Deploy

```bash
# Backend
sudo cp -r backend /var/www/api
sudo chown -R www-data:www-data /var/www/api

# Frontend
sudo cp -r frontend /var/www/html/musicschool
sudo chown -R www-data:www-data /var/www/html/musicschool

# Enable HTTPS
sudo certbot --apache -d musicschool.com -d api.musicschool.com
```

Vedi [SUMMARY.md - Deployment](SUMMARY.md#-deployment-produzione) per guida completa.

## 📞 Supporto

Hai bisogno di aiuto?

- 📖 Leggi la [documentazione completa](SUMMARY.md)
- 🐛 Apri una [issue su GitHub](https://github.com/gpdonnarumma/appuntamento/issues)
- 💬 Contatta via email

## 🗺️ Roadmap

### v1.1 (Prossima Release)
- [ ] Upload foto profilo
- [ ] Export calendario (iCal)
- [ ] Integrazione email provider
- [ ] Statistiche avanzate

### v1.2 (Futuro)
- [ ] Chat docente-studente
- [ ] Pagamenti online (Stripe)
- [ ] App mobile (PWA)
- [ ] Multi-lingua (i18n)

### v2.0 (Long-term)
- [ ] Video lezioni integrate
- [ ] Materiale didattico
- [ ] Sistema presenze
- [ ] Analytics dashboard

## ⭐ Star History

Se ti piace il progetto, lascia una ⭐ su GitHub!

---

<p align="center">
  Made with ❤️ for Music Schools
</p>

<p align="center">
  <a href="SUMMARY.md">📖 Documentazione Completa</a> •
  <a href="backend/README.md">🔧 API Docs</a> •
  <a href="frontend/README.md">🎨 Frontend Guide</a>
</p>

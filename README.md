# Webapp per la Gestione delle Lezioni di una Scuola di Musica

La webapp permette a scuole di musica, docenti e studenti di organizzare in modo intuitivo l’intero calendario delle lezioni.  
Gli utenti previsti sono:

- **Amministratore**
- **Docente / Insegnante**
- **Studente**

---

## 1. Area Docente

### Registrazione
- Il docente si registra con la propria email.
- Inserisce i dati anagrafici obbligatori: nome, cognome, data di nascita.
- Può aggiungere una foto profilo.
- Alla conferma della registrazione viene generato un **ID univoco alfanumerico**, visibile e copiabile dalla propria area utente.

### Gestione corsi e studenti
- Il docente può creare i corsi manualmente o selezionandoli da un menù a tendina con ricerca (es: pianoforte, chitarra, canto…).
- Ogni studente può inviare una richiesta di iscrizione a uno dei corsi del docente.
- Il docente visualizza una notifica numerata in “Studenti” con le richieste di approvazione.
- Per ogni richiesta viene mostrato: nome e cognome dello studente, corso richiesto, pulsante **“Aggiungi studente al corso”**.
- Il docente può accettare o rifiutare la richiesta.

### Calendario e lezioni
- Il docente visualizza gli studenti approvati e i loro corsi.
- Può prenotare lezioni per ogni studente tramite un calendario stile “booking”.
- È disponibile l’opzione **“Ripeti ogni settimana/mesi”** per creare lezioni ricorrenti.
- Può modificare, spostare o annullare una lezione.  
- Ogni modifica richiede la conferma tramite pulsante **“Conferma”**.
- Durante l’annullamento della lezione può scegliere **“Non assegnare”**, evitando di inviare comunicazioni a studenti che hanno attivato l’opzione “Avvisami quando si libera uno spazio”.
- Ogni modifica invia una mail **solo allo studente interessato**.

### Campi aggiuntivi
- Ogni lezione può contenere:
  - **Note personali** (visibili solo al docente)
  - **Obiettivi/compiti** (visibili a docente, studente e amministratore)
- Il docente può modificare i dati dello studente solo se non esiste un amministratore; in caso contrario viene inviata una mail all’amministratore per approvazione.

---

## 2. Area Studente

### Registrazione
- Lo studente si registra con la propria email o quella del genitore.
- Inserisce nome, cognome e data di nascita.
- Alla registrazione viene generato un **ID univoco alfanumerico**, visibile e copiabile nel proprio profilo.

### Iscrizione ai corsi
- Lo studente può iscriversi a più corsi tramite la barra di ricerca inserendo l’ID del docente.
- La ricerca mostra i corsi disponibili del docente.
- Ogni corso presenta il pulsante **“Invia richiesta di iscrizione”**.
- L’approvazione del docente genera una mail automatica.

### Area personale
Lo studente può visualizzare:
- La propria anagrafica (non modificabile da lui).
- Il calendario delle lezioni.
- L’elenco cronologico delle lezioni passate.
- La sezione **“Prossima lezione”** con data e ora.
- Le opzioni:
  - **“Avvisami quando si libera uno spazio”** (notifica quando il docente cancella una lezione).
  - Promemoria automatico un’ora prima della lezione (disattivabile).

---

## 3. Area Amministratore (Scuola)

### Registrazione
- L’amministratore si registra come **Scuola** inserendo: dati anagrafici, nome scuola, città.
- Alla scuola viene generato un **ID univoco alfanumerico** con prefisso `SC`.

### Ricerca da parte dei docenti
- Il docente può cercare la scuola tramite ID o nome.
- I risultati mostrano: nome scuola, ID, città.
- Il docente può inviare la richiesta tramite pulsante **“Aggiungi scuola”**.
- L’amministratore riceve una mail con i dati del docente richiedente.

### Gestione scuola
L’amministratore visualizza:
- Lista completa degli studenti associati alla scuola.
- Lista dei docenti associati.
- Le programmazioni delle lezioni di ciascun docente.

### Gestione delle lezioni
Ogni lezione contiene:
- Data
- Orario di inizio e fine
- Aula (opzionale)
- Note (visibili solo al docente)
- Obiettivi/compiti (visibili a studente, docente e amministratore)

Note e obiettivi possono essere modificati da docente e amministratore.

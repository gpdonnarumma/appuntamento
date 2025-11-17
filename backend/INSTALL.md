# Guida all'Installazione

## Requisiti

- **PHP >= 7.4** (testato con PHP 8.x)
- **Estensione PDO SQLite** (pdo_sqlite)
- **Apache o Nginx** (opzionale, si può usare il server PHP integrato)

## Installazione Estensione SQLite

### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install php-sqlite3 php-pdo
sudo systemctl restart apache2  # se usi Apache
```

### CentOS/RHEL
```bash
sudo yum install php-pdo php-sqlite
sudo systemctl restart httpd  # se usi Apache
```

### macOS (con Homebrew)
```bash
brew install php
# SQLite è già incluso
```

### Windows
1. Apri `php.ini`
2. Rimuovi il `;` davanti a:
   ```
   extension=pdo_sqlite
   extension=sqlite3
   ```
3. Riavvia il server web

### Verifica Installazione
```bash
php -m | grep -i sqlite
```
Dovresti vedere:
```
pdo_sqlite
sqlite3
```

## Avvio del Backend

### Opzione 1: Server PHP Integrato (Consigliato per sviluppo)
```bash
cd backend
php -S localhost:8000
```

Poi apri: http://localhost:8000

### Opzione 2: Apache

1. Copia la cartella `backend` nella document root di Apache
   ```bash
   sudo cp -r backend /var/www/html/
   ```

2. Assicurati che il modulo `mod_rewrite` sia abilitato:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. Accedi a: http://localhost/backend

### Opzione 3: Nginx

Configurazione di esempio per Nginx:

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/backend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Test Installazione

Esegui lo script di test:
```bash
cd backend
php test_setup.php
```

Se vedi "✓ Backend setup is complete and working!" sei pronto!

## Permessi Directory

Assicurati che la directory `database/` sia scrivibile:
```bash
chmod 755 backend/database
```

Il file `music_school.db` verrà creato automaticamente al primo avvio.

## Test delle API

### Usando curl

Registra un utente:
```bash
curl -X POST http://localhost:8000/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teacher@test.com",
    "password": "test123",
    "user_type": "teacher",
    "first_name": "Mario",
    "last_name": "Rossi",
    "birth_date": "1990-01-15"
  }'
```

Login:
```bash
curl -X POST http://localhost:8000/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teacher@test.com",
    "password": "test123"
  }'
```

### Usando Postman

1. Importa la collection dalle API descritte nel README.md
2. Usa il token ricevuto dal login nell'header `Authorization: Bearer {token}`

## Troubleshooting

### Errore: "SQLite extension not found"
- Installa l'estensione PDO SQLite (vedi sopra)
- Verifica con `php -m | grep sqlite`

### Errore: "Database connection failed"
- Verifica permessi directory `backend/database/`
- Verifica che SQLite sia installato: `php -i | grep -i sqlite`

### Errore CORS
- Se usi Apache, assicurati che `mod_headers` sia abilitato
- Verifica che `.htaccess` sia caricato

### Errore: "Headers already sent"
- Verifica che non ci siano spazi o caratteri prima di `<?php` nei file
- Verifica encoding file (deve essere UTF-8 senza BOM)

### Database non si crea
```bash
cd backend/database
php -r "require '../config/database.php'; Database::getInstance();"
```

## Configurazione Email

Per abilitare l'invio di email reali, modifica `utils/email.php`:

### Opzione 1: PHPMailer
```php
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your@email.com';
$mail->Password = 'your_password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
```

### Opzione 2: SendGrid
```php
$sendgrid = new \SendGrid('YOUR_API_KEY');
$email = new \SendGrid\Mail\Mail();
$email->setFrom("from@example.com", "Sender Name");
$email->setSubject($subject);
$email->addTo($to);
$email->addContent("text/plain", $body);
$sendgrid->send($email);
```

## Sicurezza (Produzione)

Prima di andare in produzione:

1. Cambia la chiave segreta JWT in `utils/helpers.php`:
   ```php
   function getSecretKey() {
       return getenv('JWT_SECRET_KEY'); // Usa variabile ambiente
   }
   ```

2. Disabilita display_errors in `php.ini`:
   ```ini
   display_errors = Off
   log_errors = On
   error_log = /path/to/error.log
   ```

3. Usa HTTPS sempre

4. Implementa rate limiting

5. Backup regolari del database:
   ```bash
   cp backend/database/music_school.db backup/music_school_$(date +%Y%m%d).db
   ```

## Supporto

Per problemi o domande, consulta:
- README.md - Documentazione API completa
- schema.sql - Struttura database
- File di log PHP

Buon lavoro! 🎵

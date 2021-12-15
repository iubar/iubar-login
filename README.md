[![Codacy Badge](https://app.codacy.com/project/badge/Grade/f9cb96dd46f94a589d8be55cc52188ca)](https://www.codacy.com/gh/iubar/iubar-login/dashboard)

# iubar-login

Creare twig di esempio

La classe Config del progetto iubar-login recupera i valori dalla configurazione di Slim.
Mentre il progetto originale di Panique usava un file php aggiuntivo come il seguente C:\Users\Daniele\workspace_php\php-fatturatutto\www\src\Application\Services\Login\config\config.php
Come hai coniugato entrambe le cose ?
Serve comunque un file di configurazione di esempio per il progetto iubar-login

Config::get getInstance

CHANGE LOG
Aggiunti i campi alla tabella Userexternal

`RefreshToken` VARCHAR(255) NULL,
`ClientId` VARCHAR(255) NULL, 
'LastIp'

rimosso metodo setRegNewUserModel(), getRegNewUserModel() e interfaccia IRegistration

TODO:

implementare tutti i twig referenziati
trasforamre in costanti tutte le rotte


Confrontare:

$now = new \DateTime();

$now = new \DateTime();
$now->setTimestamp(time());

http://php.net/manual/en/datetime.construct.php



Altri riferimenti importanti:
https://github.com/GoogleChrome/chromeos_smart_card_connector/issues/25






// Riferimenti
// http://doctrine-orm.readthedocs.org/en/latest/reference/tools.html?highlight=reverse
// http://doctrine-orm.readthedocs.org/en/latest/cookbook/entities-in-session.html?highlight=entities%20from



INFO:

php ./../../composer.phar show --installed

CREAZIONE CLASSI:

".\vendor\bin\doctrine.php.bat" orm:convert-mapping --force --from-database --namespace="Application\Models\\" xml .\src\Application\Models\metadata\
  
".\vendor\bin\doctrine.php.bat" orm:generate-entities .\src\ --generate-annotations=true --update-entities="true" --generate-methods="true"

VERIFICA:

".\vendor\bin\doctrine.php.bat" orm:validate-schema

INFO:

".\vendor\bin\doctrine.php.bat" orm:info

COME CREARE E AGGIORNARE LO SCHEMA (DB):

".\vendor\bin\doctrine.php.bat" orm:schema-tool:create
".\vendor\bin\doctrine.php.bat" orm:schema-tool:update --dump-sql




// bat file

@ECHO OFF

mkdir EXPORT
call ".\vendor\bin\doctrine.php.bat" orm:info

pause 


# Links
 * https://hybridauth.github.io/
 * http://www.opauth.org/ https://github.com/opauth/opauth
 * https://hybridauth.github.io/hybridauth/
 * https://github.com/socialConnect/auth
 * https://cartalyst.com/manual/sentinel-social/2.0
 * https://github.com/laravel/socialite
 * https://laravel.com/docs/5.5/passport

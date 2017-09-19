[![Codacy Badge](https://api.codacy.com/project/badge/Grade/edbddedc8bb34647bb181f5e7d775498)](https://www.codacy.com/app/Iubar/iubar-login?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=iubar/iubar-login&amp;utm_campaign=Badge_Grade)

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

=========
Contatta
=========

Questo repository contiene l'integrazione Contatta-NethServer, in particolare:

- consente di configurare dall'interfaccia di FreePBX le opzioni del dialplan necessarie per Contatta
- configura SAMBA per rendere accessibile /var/spool/asterisk/monitor
- consente di creare ed eliminare con delle REST API degli interni appartenenti al contesto webcall

Installazione
==============

Installare l'RPM di contatta: ::

    yum install contatta-*.ns7.noarch.rpm

Raggiungere l'interfaccia del modulo di FreePBX all'indirizzo https://IP/freepbx/admin/config.php?display=contatta

Configurazione
==============

Dall'interfaccia del modulo di FreePBX è possibile configurare:

- AGI IP 1: è il primo IP dove si trova l'AGI
- AGI IP 2: è il IP dove si trova l'AGI per gestire l'HA
- Monitor Exec: consente di scegliere come realizzare le registrazioni
- Enable AMI user consente di abilitare un utente per l'AMI
- La password per l'utente AMI è random e può essere modificata

Salvando e applicando i cambiamenti, le configurazioni vengono scritte nel dialplan.

REST API
=========

Vengono esposte due API che consento la creazione e l'eliminazione di interni appartenenti al contesto "webcall"

le API possono essere chiamate utilizzando una API KEY generata all'installazione del pacchetto e conservata nel file /var/lib/nethserver/secrets/contatta

Valorizzare la variabile SecretKey per gli esempi: ::

    SecretKey=$(cat /var/lib/nethserver/secrets/contatta)

POST /freepbx/contatta/extension/<EXTENSION>  data: { 'context' : <CONTEXT>, 'secret' : <SECRET> }  : crea l'extension <EXTENSION> nel contesto <CONTEXT> e la password <SECRET> (il contesto di default se omesso è: "webcall" e la password se omessa viene generata casualmente)

esempio: ::

    curl 'https://192.168.122.75/freepbx/contatta/extension/405' -H 'Secretkey: $SecretKey' --data '' -kv

genera l'interno 405 nel contesto webcall e restituisce un json contenente eventuali errori o warning e il secret dell'interno: ::

    {
        "errors": [],
        "extension": "405",
        "infos": [],
        "secret": "08ulR13B3lETsbV",
        "status": true,
        "warnings": []
    }

NOTA: gli interni appartengono al contesto webcall e non possono chamare numeri deversi dagli agenti. Tuttavia se venissero modificati dall'interfaccia di FreePBX, il contesto verrebbe perso. Questi interni non vanno modificati dall'interfaccia di FreePBX ma solamente tramite le REST API

DELETE /freepbx/contatta/extension/<EXTENSION> : elimina l'extension <EXTENSION>

esempio: ::

    curl 'https://192.168.122.75/freepbx/contatta/extension/405' -H 'Secretkey: $SecretKey' -X DELETE -kv

elimina l'extension 405 e restituisce un json contenente eventuali errori o warning: ::

    {
        "errors": [],
        "infos": [],
        "status": true,
        "warnings": []
    }

GET /contatta/trunk : ritorna la lista dei fasci con i loro dettagli

esempio: ::

    curl -kv 'https://localhost/freepbx/contatta/trunk' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' | jq

risultato: ::

    200 OK

    [
      {
          "trunkid": "1",
          "tech": "pjsip",
          "channelid": "Test trunk",
          "name": "Test trunk",
          "outcid": "",
          "keepcid": "off",
          "maxchans": "",
          "failscript": "",
          "dialoutprefix": "",
          "usercontext": "",
          "provider": "",
          "disabled": "off",
          "continue": "off",
          "dialopts": false,
          "username": "user"
      },
      ...
    ]

POST /contatta/trunk[/trunkid] : crea un nuovo fascio con i dati specificati nel body. Se si specifica il trunkid, questo verrà eliminato e ricreato con i dati del body
i parametri obbligatori sono:
name
outcid
sipserver
sipserverport
context
authentication
registration
username
secret
contactuser
fromdomain
fromuser
codecs

esempio: ::

    curl -kv 'https://localhost/freepbx/contatta/trunk' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' --data '{"name":"Test trunk","outcid":"","sipserver":"sip.foo.bar","sipserverport":"5060","context":"from-trunk","authentication":"foofoo","registration":"send","username":"username","secret":"secret","contactuser":"zz","fromdomain":"sss","fromuser":"1234","codecs":[{"nome":"alaw","enabled":1,"position":1},{"nome":"ulaw","enabled":true,"position":2}]}'

risultato: ::

    200 OK

    {"trunkid":6}

DELETE /contatta/trunk/<trunkid> : elimina il fascio specificato

esempio: ::

    curl -kv 'https://localhost/freepbx/contatta/trunk/6' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;c -X DELETE-8'

risultato: ::

    204 No Content

GET /contatta/inboundroute : restituisce la lista delle rotte in ingresso con i loro dettagli

esempio: ::

     curl -kv 'https://localhost/freepbx/contatta/inboundroute' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' | jq

risultato: ::

    200 OK

    [
      {
        "cidnum": "",
        "extension": "",
        "destination": "app-blackhole,hangup,1",
        "privacyman": "0",
        "alertinfo": "",
        "ringing": "",
        "fanswer": "",
        "mohclass": "default",
        "description": "Test Inbound",
        "grppre": "",
        "delay_answer": "0",
        "pricid": "",
        "pmmaxretries": "",
        "pmminlength": "",
        "reversal": "",
        "rvolume": "",
        "indication_zone": "default"
      },
      {
        "cidnum": "1234",
        "extension": "1245678",
        "destination": "app-blackhole,hangup,1",
        "privacyman": "0",
        "alertinfo": "<http://www.notused.com>\\;info=ring2",
        "ringing": "CHECKED",
        "fanswer": "CHECKED",
        "mohclass": "default",
        "description": "ddd",
        "grppre": "",
        "delay_answer": "0",
        "pricid": "",
        "pmmaxretries": "",
        "pmminlength": "",
        "reversal": "",
        "rvolume": "",
        "indication_zone": "default"
     }
    ]

POST /contatta/inboundroute : crea una nuova rotta in ingresso
i parametri del body sono:
cidnum
description
extension (did)
destination
fanswer (opzionale) default: ""
delay_answer (opzionale) default: "0"
rvolume (opzionale) default: ""
privacyman (opzionale) default: "0"
pmmaxretries (opzionale) default: ""
pmminlength (opzionale) default: ""
alertinfo (opzionale) default: ""
ringing (opzionale) default: ""
reversal (opzionale) default: ""
mohclass (opzionale) default: "default"
grppre (opzionale) default: ""
pricid (opzionale) default: ""
rnavsort (opzionale) default: "description"
didfilter (opzionale) default: ""
indication_zone (opzionale) default: "default"

esempio: ::

     curl -kv 'https://localhost/freepbx/contatta/inboundroute' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' --data '{"cidnum":"","description":"Test Inbound","extension":"","destination":"app-blackhole,hangup,1"}'

risultato: ::

    200 OK

    {
      "cidnum": "",
      "extension": "",
      "destination": "app-blackhole,hangup,1",
      "privacyman": "0",
      "alertinfo": "",
      "ringing": "",
      "fanswer": "",
      "mohclass": "default",
      "description": "Test Inbound",
      "grppre": "",
      "delay_answer": "0",
      "pricid": "",
      "pmmaxretries": "",
      "pmminlength": "",
      "reversal": "",
      "rvolume": "",
      "indication_zone": "default"
    }

DELETE /contatta/inboundroute : elimina la rotta definita da cidnum ed extension che devono essere specificati nel body

esempio: ::

     curl -kv 'https://localhost/freepbx/contatta/inboundroute' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' --data '{"cidnum": "","extension": ""}' -X DELETE

risultato: ::

    204 No Content


GET /contatta/outboundroute : restituisce la lista delle rotte in uscita con i loro dettagli

esempio: ::

     curl -kv 'https://localhost/freepbx/contatta/outboundroute' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' | jq

risultato: ::

    200 OK

    [
      {
        "route_id": "11",
        "name": "Test outbound route",
        "outcid": "",
        "outcid_mode": "",
        "password": "",
        "emergency_route": "",
        "intracompany_route": "",
        "mohclass": "default",
        "time_group_id": null,
        "dest": "",
        "time_mode": "",
        "calendar_id": null,
        "calendar_group_id": null,
        "timezone": "",
        "seq": "5",
        "trunks": [
          "1",
          "2"
        ],
        "patterns": [
          {
            "route_id": "11",
            "match_pattern_prefix": "+39",
            "match_pattern_pass": "0ZXXX.",
            "match_cid": "",
            "prepend_digits": ""
          },
          {
            "route_id": "11",
            "match_pattern_prefix": "0039",
            "match_pattern_pass": "0ZXXX.",
            "match_cid": "",
            "prepend_digits": ""
          }
        ]
      },
      ...
    ]

POST /contatta/outboundroute[/<route_id>] :  crea una nuova rotta in uscita o modifica un rotta esistente se specificato il route_id
i parametri del body sono:
name
outcid (opzionale) default: ""
outcid_mode (opzionale) default: ""
password (opzionale) default: ""
emergency_route (opzionale) default: ""
intracompany_route (opzionale) default: ""
mohclass (opzionale) default: "default"
time_group_id (opzionale) default: NULL
patterns (opzionale) default: ""
trunks (opzionale) default: ""
seq (opzionale) default: NULL
dest (opzionale) default: ""
time_mode (opzionale) default: ""
timezone (opzionale) default: ""
calendar_id (opzionale) default: ""
calendar_group_id (opzionale) default: ""

esempio: ::

    curl -kv 'https://localhost/freepbx/contatta/outboundroute' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' --data '{"name":"Test outbound route","patterns":[{"match_pattern_prefix":"+39", "match_pattern_pass":"0ZXXX.", "match_cid":"", "prepend_digits":""},{"match_pattern_prefix":"0039", "match_pattern_pass":"0ZXXX.", "match_cid":"", "prepend_digits":""}],"trunks":[1,2]}'

risultato: ::

    201 Created

DELETE /contatta/outboundroute/<route_id> : elimina la rotta con id route_id

esempio: ::

     curl -kv 'https://localhost/freepbx/contatta/outboundroute/4' -H 'Accept: application/json, text/plain, */*' -H 'User: admin' -H "Secretkey: $SecretKey" -H 'Content-Type: application/json;charset=utf-8' -X DELETE

risultato: ::

    204 No Content

Certificato
===========

- Versione Community: usare il modulo certman

- Versione Enterprise: il certificato usato è quello di NethServer

Samba
=====

Viene condivisa la cartella /var/spool/asterisk/monitor

G729
====

Per installare ed attivare il codec g729 Open Source ecco la procedura (comporta il riavvio di Asterisk e quindi l’eventuale caduta di chiamate in corso): ::

    cd /usr/lib64/asterisk/modules/
    wget http://asterisk.hosting.lv/bin/codec_g729-ast130-gcc4-glibc-x86_64-pentium4.so
    mv codec_g729-ast130-gcc4-glibc-x86_64-pentium4.so codec_g729.so
    chmod 755 codec_g729.so
    systemctl restart asterisk

Il codec g729 Open Source non è compatibile con la versione a pagamento di Digium, che si può installare seguendo la procedura che vi forniranno con l’acquisto.

E' possibile, quindi, utilizzare contemporaneamente solo una delle due versioni di g729, Open Source o Digium.

Informazioni aggiuntive per lo sviluppo
========================================

RPM
---

l'RPM di contatta contiene:

- un modulo di FreePBX (messo in /usr/src/contatta/modules/contatta.tar.gz ed installato dentro /var/www/html/freepbx/admin/modules/contatta dall'azione /etc/e-smith/events/actions/contatta-update)
- il codice delle REST API, installate in /var/www/html/freepbx/contatta
- la configurazione di Samba: nel frammento di template /etc/e-smith/templates/etc/samba/smb.conf/95contatta espanso dall'evento di installazione contatta-update

La build dell'RPM può essere fatta da un NethServer o da Fedora.

- Installare nethserver-mock http://docs.nethserver.org/projects/nethserver-devel/en/latest/building_rpms.html#nethserver-mock
- lanciare lo script che crea l'archivio del modulo di FreePBX e lo firma. Non è indispensabile essere in possesso di una chiave firmata da Sangoma, ma serve per evitare che nell'interfaccia di FreePBX compaia l'allarme di "moduli non firmati" https://wiki.freepbx.org/display/FOP/Requesting+a+Key+to+be+Signed ::

    ./retrieve_modules.sh GPG-KEY-SIGNATURE GPGPASSPHRASE

- lanciare il comando per creare l'RPM: ::

    make-rpms contatta.spec

Il modulo di FreePBX
---------------------

- Il modulo di FreePBX contiene l'interfaccia web di configurazione in /var/www/html/freepbx/admin/modules/contatta/views/form.php e la funzione necessaria per scrivere il dialplan (funzione doDialplanHook dentro /var/www/html/freepbx/admin/modules/contatta/Contatta.class.php)
- le configurazioni del modulo vengono salvate nella tabella mysql asterisk.kvstore_FreePBX_modules_Contatta

Rest API
---------

- le API sono fornite grazie al framework Slim https://www.slimframework.com/
- l'autenticazione è definita in /var/www/html/freepbx/contatta/lib/AuthMiddleware.php e al momento si limita a verificare che la "Secretkey" fornita nell'header sia uguale a quella generata in fase di installazione e salvata in /var/lib/nethserver/secrets/contatta
- le API sono nel file /var/www/html/freepbx/contatta/modules/extensions.php, utilizzano la libreria aggiuntiva /var/www/html/freepbx/contatta/lib/libExtensions.php. E' possibile da qui chiamare tutte le funzioni di FreePBX grazie all'include di /etc/freepbx.conf in /var/www/html/freepbx/contatta/index.php
- Tutti i cambiamenti apportati dalle funzioni di FreePBX vengono salvati sul database mysql di FreePBX (database asterisk) e vengono effettivamente scritte nei file di configurazione di Asterisk quando da interfaccia viene premuto il tasto "Applica cambiamenti". Nel caso delle API, i cambiamenti vengono applicati dallo script /var/www/html/freepbx/contatta/lib/retrieveHelper.sh



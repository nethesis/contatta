#!/bin/bash
if [[ $# < 2 ]]; then
    echo "Usage: ./retrieve_modules.sh <GPG-KEY-ASH> <PASSPHRASE>"
    exit 2
fi

GPGASH=$1
PASSPHRASE=$2

rm -fr contatta.tar.gz

# Sign the module 
[[ -n "${PASSPHRASE}" ]] && /usr/bin/expect <<EOD
spawn ./sign.php module/ $GPGASH
expect "Enter passphrase:"
send "$PASSPHRASE\n"
expect eof
EOD
    
# Pack the module
tar czpf contatta.tar.gz module


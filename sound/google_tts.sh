#!/bin/sh
#
# Convert text to voice (Google TTS API).
# Require: gcloud package.
#

OUTF=`echo $2 | sed 's/.json/.lin16/'`
echo $OUTF

curl -X POST \
-H "Authorization: Bearer "$1 \
-H "Content-Type: application/json; charset=utf-8" \
-d @$2 \
"https://texttospeech.googleapis.com/v1/text:synthesize" \
| sed -n 2P | sed 's/  "audioContent": "//' \
| base64 -d > $OUTF 2>/dev/null

exit 0

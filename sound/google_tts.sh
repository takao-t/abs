#!/bin/sh
#
# Convert text to voice (Google TTS API).
# Require: gcloud package.
#
export GOOGLE_APPLICATION_CREDENTIALS="YOUR_CREDENTIAL_HERE"

OUTF=`echo $1 | sed 's/.json/.lin16/'`
echo $OUTF

curl -X POST \
-H "Authorization: Bearer "$(gcloud auth application-default print-access-token) \
-H "Content-Type: application/json; charset=utf-8" \
-d @$1 \
"https://texttospeech.googleapis.com/v1/text:synthesize" \
| sed -n 2P | sed 's/  "audioContent": "//' \
| base64 -d > $OUTF 2>/dev/null

exit 0

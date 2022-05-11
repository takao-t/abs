#!/bin/sh

export GOOGLE_APPLICATION_CREDENTIALS="YOUR_CREDENTIAL_WILL_COME_HERE"
gcloud auth application-default print-access-token

exit 0

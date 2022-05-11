# Makefile

TOKEN := $(shell ../../google_auth.sh)
SRCS= $(wildcard *.json)
SLINS= $(patsubst %.json,%.lin16,$(SRCS))
WAVS= $(patsubst %.json,%.wav,$(SRCS))

all: $(SLINS) $(WAVS)

%.lin16: %.json
	@../../google_tts.sh $(TOKEN) $<

%.wav: %.lin16
	cat $< | sox -t wav - -r 8000 -c 1 $@ gain -6

clean:
	rm -f *.lin16 *.wav

install: $(WAVS)
	cp *.wav /var/lib/asterisk/sounds/ja/letters/.

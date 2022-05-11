#
DIRS = ja ja/digits ja/letters
MHOME := $(dir $(abspath $(lastword $(MAKEFILE_LIST))))

all:
	for subd in $(DIRS); do \
	  echo $$subd; \
	  cd $$subd; \
	  make; \
	  cd $(MHOME); \
	done

install:
	for subd in $(DIRS); do \
	  echo $$subd; \
	  cd $$subd; \
	  make install; \
	  cd $(MHOME); \
	done

clean:
	for subd in $(DIRS); do \
	  echo $$subd; \
	  cd $$subd; \
	  make clean; \
	  cd $(MHOME); \
	done

tgz:
	tar cvfz abs-sounds-ja.tgz ja/*.wav ja/digits/*.wav ja/letters/*.wav

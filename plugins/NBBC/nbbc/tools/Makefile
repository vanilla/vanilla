# This makefile uses merge.pl to generate nbbc.php from its component
# files when they change.

SRCS	=	../src/nbbc_main.php ../src/nbbc_lib.php ../src/nbbc_lex.php \
			../src/nbbc_email.php ../src/nbbc_parse.php

TARGET	=	../nbbc.php

all: $(TARGET)

$(TARGET): $(SRCS) merge.pl
	./merge.pl > $(TARGET)

clean:
	rm -f $(TARGET)


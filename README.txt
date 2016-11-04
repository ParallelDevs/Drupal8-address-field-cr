Adds a simple Costa Rican address field type and form for content types.
======================================================

DESCRIPTION
------------
The Costa Rican address system is quite unique, making it difficult to store Costa Rican addresses as fields in entity types by default. This module provides a simple set of fields that dynamically populate to simplify the process of entering addresses and storing them in the database. In an entity type with a Costa Rican address field added, enter an address in one of two ways:

1) Select a Province, Canton, and District. The module generates the corresponding zipcode and displays it.
2) Enter a given zipcode, and the module will populate the Province, Canton, and District fields with the appropriate values.


INSTALLATION
------------
1. Download and unpack the "Address Field CR" module directory in your modules folder
2. Go to "Administer" -> "Modules" and enable the "AddressField CR" module.


CONFIGURATION
-------------
Add a field type of Address Field CR to any content type.

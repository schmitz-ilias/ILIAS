# Roadmap

### Towards Vocabularies

To pave the way for the introduction of custom vocabularies, 
as a first step one can allow custom inputs for vocabulary input
fields e.g. as a text input in a switchable group. This entails:
* Introduction of 'source' fields for every vocabulary field in
  the database.
* Modify the VOCAB_SOURCE constraint in the MDDataFactory.

### Stricter formatting of 'format' and 'entity'

The fields technical>format and the various entities should conform
to different standards (e.g. entities should be vcards). This could
be supported better in ILIAS, currently any string is valid.

### Vocabularies

Allow adding other vocabularies than LOM. This could be implemented
along similar lines as the 'copyright' tab in the administration
settings for MD.

### Abandon the old backend

All ILIAS components using MD should at some point only use the
new classes as the new MD editor does.

### Customizable LOM Digest

Allow customizing what elements are part of the LOM Digest in
administration settings.
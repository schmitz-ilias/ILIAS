# Roadmap

### Location Type
Check whether the field 'location_type' in the table 
'il_meta_location' can be removed. Location type can be set in
the old MD editor, but is not part of the LOM standard. It
would be nice to get rid of it, should it not be used anywhere
else in ILIAS.

### Streamline the query construction in the DB dictionary
Currently, the methods constructing the bespoke queries for
the MD elements in ilMDLOMDatabaseDictionary are a bit of
a mess, with a lot of overlap between them. This can be done in 
a more elegant way.

### Towards Vocabularies

To pave the way for the introduction of custom vocabularies, 
as a first step one can allow custom inputs for vocabulary input
fields e.g. as a text input in a switchable group. This entails:
* Introduction of 'source' fields for every vocabulary field in
  the database.
* Modify the VOCAB_SOURCE constraint in the MDDataFactory.
* 
Please beware of 'type' and 'name' in technical>requirement>orComposite
as a potential stumbling block.

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
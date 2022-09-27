# Roadmap

### Performance

Check the performance of the repository, as a lot of cloning of
structures is happening there. An easy first step towards improving
performance should be unsetting the clones when appropriate.

### Location Type

Check whether the field 'location_type' in the table 
'il_meta_location' can be removed. Location type can be set in
the old MD editor, but is not part of the LOM standard. It
would be nice to get rid of it, should it not be used anywhere
else in ILIAS.

### Refactor ilMDCopyrightSelectionEntry

The class ilMDCopyrightSelectionEntry could need some refactoring
(get rid of static functions, etc.). Maybe it would make sense
to roll this into the custom vocabularies.

This might also apply to related classes (e.g. 
ilOerHarvesterSettings).

### Streamline the query construction in the DB dictionary

Currently, the methods constructing the bespoke queries for
the MD elements in ilMDLOMDatabaseDictionary are a bit of
a mess, with a lot of overlap between them. This can be done in 
a more elegant way.

### More filters for paths

Useful would be a filter that checks whether the element at
the end of a relative path has a specific value (e.g. to find
authors one would need to check the 'role' element of
'contribute' elements.)

### Towards Vocabularies

To pave the way for the introduction of custom vocabularies, 
as a first step one can allow custom inputs for vocabulary input
fields e.g. as a text input in a switchable group. This entails:
* Introduction of 'source' fields for every vocabulary field in
  the database.
* Modify the VOCAB_SOURCE constraint in the MDDataFactory, probably
  making it conditional on the corresponding value.

Please beware of 'type' and 'name' in technical>requirement>orComposite
as a potential stumbling block.

### Stricter formatting of 'format' and 'entity'

The fields technical>format and the various entities should conform
to different standards (e.g. entities should be vcards). This could
be supported better in ILIAS, currently any string is valid.

### Streamline everything

There are a few concepts that are very similar (elements with
super- and sub-elements, structures, paths...). However,
I'm not quite sure whether they are similar enough to replace
them entirely, as they currently fulfill different functions.
It should be checked whether there are redundancies there.

### Vocabularies

Allow adding other vocabularies than LOM. This could be implemented
along similar lines as the 'copyright' tab in the administration
settings for MD. Note that the usage of non-LOMv1.0 sources for
vocabularies in a MD set means that also the element 'metadataSchema'
has to be appendend, see the LOM standard.

### Abandon the old backend

All ILIAS components using MD should at some point only use the
new classes as the new MD editor does.

### Customizable LOM Digest

Allow customizing what elements are part of the LOM Digest in
administration settings. It would also be worth thinking about 
how to implement multilinguality in the Digest.
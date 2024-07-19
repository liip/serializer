# Changelog

# 2.6.1

* Fixed: Dates with a getter/setter where incorrectly handled in the refactoring for 2.6.0.
  See (#44)[https://github.com/liip/serializer/pull/44]
* Add support for symfony `7.x`
* Also test against PHP `8.3`
* Update rector to `1.2.1`

# 2.6.0

* (De)serialization now accepts timezones, and lists of deserialization formats

# 2.5.1

* Generalized the improvement on arrays with primitive types to generate more efficient code.

# 2.5.0

* Clean CI workflow: fix GitHub composer caches
* Add rector-php analysis
* Increase Phpstan check level to 7
* Fix fallback to JMS serializer when the order of configured groups were
  not ordered as the ones in the generated PHP filenames.
  Keep consistent sorting on both Context and GroupCombination classes.
* Fix bug when serializing a multidimensional array with a primitive type.

# 2.4.0

* Increase liip/metadata to `1.1` and drop support for `0.6`
* Clean up build process

# 2.3.1

* Allow installation with liip/metadata 1.x in addition to 0.6

# 2.3.0

* Fixed deprecation warnings for PHP 8
* Dropped support for PHP 7

# 2.2.0

* Add new parameter `$options` to the `GenerateConfiguration` class
* Support (de)serializing arrays with undefined content by setting the
  `allow_generic_arrays` option to `true`.

# 2.1.0

* Add support for generating recursive code up to a specified maximum depth
  that can be defined via the `@MaxDepth` annotation/attribute from JMS
* Add support for (de-)serializing doctrine collections

# 2.0.6

* Allow installation with liip/metadata-parser 0.5
* Test with PHP 8.1

# 2.0.5

* Allow installation with liip/metadata-parser 0.4 and Symfony 6

# 2.0.4

* Support PHP 8
* Allow installation with liip/metadata-parser 0.3 and jms/serializer 3

# 2.0.3

* [DX]: Context now removes duplicates in groups.
* [DX]: Better exception message for unknown constructor argument.

# 2.0.2

* [Bugfix]: Respect group configuration when no version is specified.

# 2.0.1

* [Bugfix]: Fix deserialization of DateTime with a format.

# 2.0.0

* [BC Break]: Configuration of the serializer generator changed to configuration model.
  The new format allows to more precisely specify which serializers to generate.

# 1.0.0

Initial release

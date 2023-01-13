# Changelog

# 2.2.0 (unreleased)

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

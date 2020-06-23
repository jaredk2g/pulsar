[Return to main page](/pulsar)

Validators
=====

- [alpha](#alpha)
- [alpha_dash](#alpha_dash)
- [alpha_numeric](#alpha_numeric)
- [boolean](#boolean)
- [callable](#callable)
- [date](#date)
- [db_timestamp](#db_timestamp)
- [encrypt](#encrypt)
- [email](#email)
- [enum](#enum)
- [ip](#ip)
- [matching](#matching)
- [numeric](#numeric)
- [password](#password)
- [range](#range)
- [required](#required)
- [string](#string)
- [time_zone](#time_zone)
- [timestamp](#timestamp)
- [unique](#unique)
- [url](#url)

## alpha

Validates an alpha string.

Options:
- min: specifies a minimum length

## alpha_dash

Validates an alpha-numeric string with dashes and underscores.

Options:
- min: specifies a minimum length

## alpha_numeric

Validates an alpha-numeric string.

Options:
- min: specifies a minimum length

## boolean

Validates a boolean value.

## callable

Calls a custom validation function.

Options:
- fn: specifies a callable value (required)

## date

Validates a date string.

## db_timestamp

Converts a Unix timestamp into a format compatible with database
timestamp types.

## encrypt

Encrypts a string value using defuse/php-encryption.

In order for this validation rule to work it requires
that the defuse/php-encryption library is installed and
that the encryption key has been set with Type::setEncryptionKey().

## email

Validates an e-mail address.

## enum

Validates a value matches one of the available choices.

Options:
- choices: specifies a list of valid choices (required)

## ip

Validates an IP address.

## matching

Validates that an array of values matches. The array will
be flattened to a single value if it matches.

## numeric


Validates a number.

Options:
- type: specifies a PHP type to validate with is_(defaults to numeric)

## password


Validates a password and hashes the value using
password_hash().

Options:
- min: minimum password length
- cost: desired cost used to generate hash

## range

Validates that a number falls within a range.

Options:
- min: minimum value that is valid
- max: maximum value that is valid

## required

Makes sure that a variable is not empty.

## string

Validates a string.

Options:
- min: specifies a minimum length
- max:  specifies a maximum length

## time_zone

Validates a PHP time zone identifier.

## timestamp

Validates a Unix timestamp. If the value is not a timestamp it will be
converted to one with `strtotime()`.

## unique

Checks if a value is unique for a property.

Options:
- column: specifies which column must be unique (required)

## url

Validates a URL.

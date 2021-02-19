# Data censor

A simple tool to censor data structures.

## Motivation

When documenting software, you often have to deal with data structures used in examples. Maybe an API response or
request data. This data often contains sensitive information like API tokens, usernames or other personal information.
Hunting these entries is tedious and should be automated, right?

## Usage

Create a `\dbx12\jsonCensor\Censor` object, call `addRule()` on it for every key you want to censor and finally send
your data into `censor()` or let Censor read it from a file and write it to another file.

A more thorough example is in `example.php`

### Paths

A path describes an element in your input data, a bit like a CSS selector does. Every path must start with a `.` and end
in a property name. In the example JSON below, you would address the field containing "john" with `.users.name`. Note
how the index in the users list is unspecified. There are no numeric indices in these path descriptions. You can specify
multiple paths which are subject to the same rule as array. Using `['.users.name','.users.email']` as first parameter
to `addRule()` and `['role' => 'Admin']` as second would remove the name and email for all admins.

### Conditions

A condition defines if the rule applies to that element. You can use the siblings of the selected element to decide
that. You define a condition as array structure, the key describes the field, the value describes what value(s) is
expected. You can use an array to define multiple allowed values. Multiple keys can be checked (all must match then).

Assume we selected `.users.name` in the example below and want to remove it if it belongs to a Customer or Subscriber.
The rule would be: `['role' => ['Customer','Subscriber']]`.

### Example JSON

```json
{
  "users": [
    {
      "name": "john",
      "role": "Admin",
      "email": "john.doe@example.org",
      "moneySpent": 0
    },
    {
      "name": "jane",
      "role": "Customer",
      "email": "jane.doe@example.org",
      "moneySpent": 100
    },
    {
      "name": "alex",
      "role": "Subscriber",
      "email": "alex.doe@example.org",
      "moneySpent": 0
    }
  ],
  "purchases": [
    {
      "customerName": "jane",
      "items": {
        "apple": 5,
        "banana": 2
      }
    }
  ]
}
```

### Strategies

The strategy defines how the value (or key and value in case of an array) are censored. All strategies must implement
the `CensorStrategyInterface`. If no strategy is specified for a rule, the `ConstantCensorStrategy` is used. This can be
changed by modifying `Censor::$defaultStrategy`.

You can easily extend the functionality of this project by defining own censor strategies. Simply implement the
`\dbx12\jsonCensor\censorStrategies\CensorStrategyInterface` with your censor logic.

## Known limitations

The system works only with scalar values (e.g. string, int) and arrays (both, numeric and associative). The behavior on
encountering something else (e.g. ressource or object instances) is undefined and most likely a crash.

# Advanced JSON (`adv_json`)

`adv_json` provides a structured JSON editor UI for storing JSON in a model column.

This field is migrated from a legacy `voyager-extension` implementation. The exact UI and options depend on the current template and can be extended via the `details` JSON for the DataRow.

## Recommended usage

Use `adv_json` when:
- you need to store structured configuration per record
- you want an editor UI instead of a plain textarea
- you are OK with application-level validation (JSON schema is not enforced by default)

## Details JSON

The field can be used with minimal configuration:

```json
{}
```

If you need to provide defaults, use the standard Voyager `default` key:

```json
{
  "default": {
    "enabled": true,
    "items": []
  }
}
```

> Note: the value is stored as JSON in the model column. Make sure the column type is `json` or `text`.

## Accessing the value in code

```php
$config = $model->config_field;
if (is_string($config)) {
    $config = json_decode($config, true);
}
```

## Validation tip

If you want to ensure the value is valid JSON, add a model cast:

```php
protected $casts = [
    'config_field' => 'array',
];
```

Then the field will be returned as an array and Laravel will re-encode it automatically.

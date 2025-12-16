# Advanced Image (`adv_image`)

`adv_image` stores a single uploaded image using Voyager's Media Storage subsystem (polymorphic `media` table). It supports basic image props and cropping.

## Details JSON

```json
{
  "collection_name": "cover"
}
```

### Options

- `collection_name` (string, optional): media collection name (defaults to the field name).

## Behaviour

- Upload replaces the previous image in the same collection.
- Props are stored in `media.props` (JSON): `title`, `alt`.
- Deleting clears the model field and removes the corresponding media record + file.
- Crop uses the same crop API as the Media Manager (CropperJS UI).

## Example: BREAD field setup

1) Create a database column (recommended type: nullable integer):

```sql
ALTER TABLE posts ADD COLUMN cover_image_id INT NULL;
```

2) In BREAD, set:
- Field: `cover_image_id`
- Type: `adv_image`
- Details:

```json
{
  "collection_name": "cover"
}
```

The model will store the `media.id` of the uploaded image in `cover_image_id`.

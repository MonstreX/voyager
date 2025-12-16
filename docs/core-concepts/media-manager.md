# Media Manager

Voyager has a full-fledged Media Manager which allows you to upload files, re-name files, and delete files. You can also add new folders and move files/folders. Basically anything that you would be able to do in any type of Media Manager you can do so in the Voyager Media Manager.

![](../.gitbook/assets/media_manager.png)

You may also drag and drop files onto the 'upload' button to upload multiple files.  
The media manager allows you to create thumbnails and add watermarks to uploaded images through the configuration file.  
Please visit the [media-picker documentation](../bread/formfields/media-picker.md#watermark) to learn about the configuration options.  

## Notes for this fork

This fork includes a custom Media Storage subsystem (polymorphic `media` table) used by `adv_image` / `adv_media_files` / `adv_inline_set`.

The Media Manager crop dialog was extended to match the advanced fields:
- Aspect ratio presets (including free crop)
- Max width / max height constraints (downscale after crop while preserving aspect ratio)
- Preview refresh after crop (cache-bust via `last_modified`)

{% hint style="info" %}
**Notice on File Upload Size**  
If you are getting an error when trying to upload large files, this may be a setting that needs to be changed in your PHP. Be sure to check `max_file_upload` and `file_upload_size`
{% endhint %}

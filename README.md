# WT On fly image handler - plugin for Joomla JoomShopping 
Processing of JoomShopping product images during editing: the same proportions, conversion to the same format, removal of transparency, naming of files. The Intervention Image library is used. Joomla 3 (JoomShopping 4) and Joomla 4 (JoomShopping 5) support.
# Why do I need a plugin?
The plugin allows you to change the size of images when editing the product of the Joomla JoomShopping online store, bring them to the same proportions, remove transparency and save all images in the same format, for example in WEBP.
# Image Conversion Settings
The plugin uses the well-known PHP image processing library Intervention Image. It allows you to save all images of JoomShopping online store products in one format (for example, in WEBP).
The plugin uses JoomShopping image size settings: JoomShopping - Settings - Images. The quality setting of the saved images is also used. The settings of the fill size and color change method are ignored. By default, the fill color is white.
**Make images square?**
If YES, The images will be reduced to square proportions based on the largest side of the image. The extra empty background will be cut off, the necessary fields will be added to the square proportion, the background will be filled with white. If NO, The image sizes will be changed according to the JoomShopping settings. Excess areas of the image are cut off. If the values of both height and width are specified, the larger side will be changed to the one specified in the settings, the smaller side will be changed proportionally. If only one value is specified - height or width - the second side will be changed proportionally. If the height and width in the settings are 0, the image will simply be saved in the specified format without resizing.
**Output file format**
The plugin can save images in GIF, PNG, JPG, WEBP formats.
**Rename image files?**
If enabled, the name of the image file will be changed to the product name according to the rules for creating aliases in Joomla: spaces are replaced with hyphens. If there are several images, a numeric index will be added to the product name. For example, the product is called "Phone Panasonic", and the image files will be called "phone-panasonic", "phone-panasonic-1", "phone-panasonic-2", etc.

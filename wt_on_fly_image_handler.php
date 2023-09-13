<?php
/**
 * @package         JoomShopping
 * @subpackage      WT On fly image handler
 *
 * @copyright   (C) 2022 Sergey Tolkachyov <https://web-tolk.ru>
 * @license         GNU General Public License version 2 or later
 * @version         1.0.1
 * @link            https://web-tolk.ru/dev/joomla-plugins/wt-on-fly-image-handler.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Filesystem\File;
use Joomla\CMS\Helper\LibraryHelper;
use Joomla\CMS\Filter\OutputFilter;

use Joomla\CMS\Version;

/**
 * @since  1.0.0
 */
class PlgJshoppingAdminWt_on_fly_image_handler extends CMSPlugin
{

	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array    $config   An array that holds the plugin configuration
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		if (!class_exists('JInterventionimage'))
		{
			if (LibraryHelper::isEnabled('jinterventionimage'))
			{
				JLoader::register('JInterventionimage', JPATH_LIBRARIES . DIRECTORY_SEPARATOR . 'jinterventionimage' . DIRECTORY_SEPARATOR . 'jinterventionimage.php');

			}
		}

	}

	/**
	 * User add images to product in admin panel and save product.
	 * Then product model store the product to database (JTable) and uploads and handles (resizes) images.
	 * We have to process the images after they have been processed by JoomShopping,
	 * so we have to delete JoomShopping files and resave our own after processing<br/>
	 * The trigger <strong>onAfterSaveProductImage</strong> fired on each image JoomShopping processing.<br/>
	 * The trigger <strong>onAfterSaveProductEnd</strong> fired once when product saved.
	 *
	 * @param $product_id   int JoomShopping product id
	 * @param $name_image   string  product image name
	 *
	 * @see   \JshoppingModelProducts::save
	 * @see   \JshoppingModelProducts::uploadImages
	 * @since 1.0.0
	 */
	public function onAfterSaveProductImage($product_id, $name_image)
	{
		
		// Передаем имя файла в следующий триггер
		$session = Factory::getSession();
		if ($session->get('wt_on_fly_image_handler_image_name'))
		{
			$image_names   = $session->get('wt_on_fly_image_handler_image_name');
			$delete_images = $session->get('wt_on_fly_image_handler_delete_images');
		}
		else
		{
			$image_names   = array();
			$delete_images = array();
		}


		$name_thumb = 'thumb_' . $name_image; // миниатюра
		$name_full  = 'full_' . $name_image; //исходное изображение.
		
		$jversion = new Version();
		if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			// only for Joomla 3.x
			$jshopConfig = JSFactory::getConfig();
		} else {
			// Joomla 4
			$jshopConfig = \JSFactory::getConfig();
		}
		
		$path_image  = $jshopConfig->image_product_path . "/" . $name_image; //обработанное изображение
		$path_thumb  = $jshopConfig->image_product_path . "/" . $name_thumb; //миниатюра изображения
		$path_full   = $jshopConfig->image_product_path . "/" . $name_full; //исходное загруженное изображение

		/**
		 * Переименовывать файлы изображений в человеко-понятные - название товара + числовой индекс
		 */


		if ($this->params->get('rename_images') == 1)
		{
			if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			// only for Joomla 3.x
				$product = JSFactory::getTable('product', 'jshop');
				$lang         = JSFactory::getLang();
			} else {
				// Joomla 4
				$product = \JSFactory::getTable('product', 'jshop');
				$lang         = \JSFactory::getLang();
			}
			
			$product->load($product_id);
			
			$product_name = $lang->get('name');

			$new_file_name      = OutputFilter::stringUrlSafe($product->$product_name);
			$tmp_new_file_image = $new_file_name . '.' . $this->params->get('output_file_format', 'jpg');

			while (file_exists($jshopConfig->image_product_path . '/' . $tmp_new_file_image))
			{
				$new_file_name      = StringHelper::increment($new_file_name, 'dash');
				$tmp_new_file_image = $new_file_name . '.' . $this->params->get('output_file_format', 'jpg');
			}

			$new_file_name = $new_file_name . '.' . $this->params->get('output_file_format', 'jpg');

		}
		else
		{
			$new_file_name = File::stripExt($name_image) . '.' . $this->params->get('output_file_format', 'jpg');
		}

		$image_names[] = [
			'old_name' => $name_image,
			'new_name' => $new_file_name,
		];
		$session->set('wt_on_fly_image_handler_image_name', $image_names);


		/**
		 * Настройки для обработки ОРИГИНАЛЬНОГО изображения
		 */
		//if (!ImageLib::resizeImageMagic($path_full, $jshopConfig->image_product_original_width, $jshopConfig->image_product_original_height, $jshopConfig->image_cut, $jshopConfig->image_fill, $path_full, $jshopConfig->image_quality, $jshopConfig->image_fill_color, $jshopConfig->image_interlace)){
		// Максимальная ширина для оригинального изображения
		// $jshopConfig->image_fill_color в своём формате. Потом докрутим.
		//удаляем файлы, сделанные JoomShopping, в следующем триггере

		if ($name_image !== $new_file_name)
		{
			$delete_images[] = $path_image;
			$delete_images[] = $path_thumb;
			$delete_images[] = $path_full;
			$session->set('wt_on_fly_image_handler_delete_images', $delete_images);
		}


		// для оригиналов - показываются в лайтбоксе на фронте

		$image_original_width  = (($jshopConfig->image_product_original_width) ? $jshopConfig->image_product_original_width : null);
		$image_original_height = (($jshopConfig->image_product_original_height) ? $jshopConfig->image_product_original_height : null);

		$options = [
			'widthFit'        => $image_original_width,
			'heightFit'       => $image_original_height,
			'new_file_name'   => 'full_' . File::stripExt($new_file_name), // оригинал изображения
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $jshopConfig->image_product_path,
			'image_quality'   => $jshopConfig->image_quality,
		];

		// Изменяем оригинал изображения
		$this->handleImage($path_full, $options);

		// для среднего изображения - показываются в карточке товара на фронте

		$image_product_full_width  = (($jshopConfig->image_product_full_width) ? $jshopConfig->image_product_full_width : null);
		$image_product_full_height = (($jshopConfig->image_product_full_height) ? $jshopConfig->image_product_full_height : null);

		$options = [
			'widthFit'        => $image_product_full_width,
			'heightFit'       => $image_product_full_height,
			'new_file_name'   => File::stripExt($new_file_name), // основное изображение
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $jshopConfig->image_product_path,
			'image_quality'   => $jshopConfig->image_quality,
		];

		// Изменяем среднее изображение изображения
		$this->handleImage($path_full, $options);

		// для тумбочки изображения - показываются в карточке товара (зависит от шаблона) и в категории товаров на фронте

		$image_product_width  = (($jshopConfig->image_product_width) ? $jshopConfig->image_product_width : null);
		$image_product_height = (($jshopConfig->image_product_height) ? $jshopConfig->image_product_height : null);

		$options = [
			'widthFit'        => $image_product_width,
			'heightFit'       => $image_product_height,
			'new_file_name'   => 'thumb_' . File::stripExt($new_file_name), // тумбочка изображения
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $jshopConfig->image_product_path,
			'image_quality'   => $jshopConfig->image_quality,
		];

		// Изменяем среднее изображение изображения
		$this->handleImage($path_full, $options);

	}

	/**
	 * Handle the image by filename
	 *
	 * @param   string  $path_full
	 * @param   array   $options
	 *
	 * @return void
	 */
	function handleImage(string $path_full, array $options): void
	{

		$widthFit  = $options['widthFit'];
		$heightFit = $options['heightFit'];

		$jversion = new Version();
		if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			// only for Joomla 3.x
			$jshopConfig = JSFactory::getConfig();
		} else {
			// Joomla 4
			$jshopConfig = \JSFactory::getConfig();
		}

		list($width, $height, $type, $attr) = getimagesize($path_full);
		$manager = JInterventionimage::getInstance(['driver' => 'imagick']);

		// https://github.com/Intervention/image/issues/551 Transparency on PNG issue
		$tmp_file = $manager->make($path_full);
		$img      = $manager->canvas($tmp_file->width(), $tmp_file->height(), '#ffffff');
		$img->insert($tmp_file);

		// Убираем белый лишний фон и добавляем рамку в 50px вокруг
		$img->trim('top-left', null, 20, 50);

		//Делаем изображения квадратными
		if ($this->params->get('square_image', 0) == 1)
		{
			$width  = $img->getWidth();
			$height = $img->getHeight();
			$ratio  = $width / $height;

			if ($ratio != 1)
			{
				if ($width > $height)
				{
					//http://image.intervention.io/api/resizeCanvas
					$new_height = $width;
					$img->resizeCanvas($width, $new_height, 'center', false, '#ffffff');
				}
				elseif ($height > $width)
				{
					$new_width = $height;
					$img->resizeCanvas($new_width, $height, 'center', false, '#ffffff');
				}


				if ($heightFit > $widthFit)
				{
					$widthFit = $heightFit;
				}
				elseif ($widthFit > $heightFit)
				{
					$heightFit = $widthFit;
				}
			}
		}

		/**
		 * Если оба значения NULL - не меняем размер, переходим к сохранению
		 */
		if (!is_null($widthFit) || !is_null($heightFit))
		{

			// resize the image so that the largest side fits within the limit; the smaller
			// side will be scaled to maintain the original aspect ratio
			// https://image.intervention.io/v2/api/resize
			$img->resize($widthFit, $heightFit, function ($constraint) {
				$constraint->aspectRatio();
				$constraint->upsize();

			});
		}


		$img->save($options['savepath'] . '/' . $options['new_file_name'] . '.' . $options['new_file_format'], $options['image_quality'], $options['new_file_format']);

	}

	/**
	 * После обработки изображений нужно заменить имена файлов (расширения файлов) в БД
	 */

	public function onAfterSaveProductEnd($product_id)
	{

		$jversion = new Version();
		if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			// only for Joomla 3.x
			$jshopConfig = JSFactory::getConfig();
		} else {
			// Joomla 4
			$jshopConfig = \JSFactory::getConfig();
		}
		$session     = Factory::getSession();
		$image_names = $session->get('wt_on_fly_image_handler_image_name');
		$session->clear('wt_on_fly_image_handler_image_name');

		// В товаре - основное изображение
		
		
		if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			// only for Joomla 3.x
			$product = JSFactory::getTable('product', 'jshop');
		} else {
			// Joomla 4
			$product = \JSFactory::getTable('product', 'jshop');
		}
		
		$product->load($product_id);
		// В таблице product_images
		$db = Factory::getDbo();


		foreach ($image_names as $image_name)
		{
			$query = $db->getQuery(true);
			if ($product && $product->image === $image_name['old_name'])
			{
				$product->image = $image_name['new_name'];
				$product->store();
				unset($product);
			}

			$fields     = [
				$db->quoteName('image_name') . ' = ' . $db->quote($image_name['new_name']),
			];
			$conditions = array(
				$db->quoteName('image_name') . ' = ' . $db->quote($image_name['old_name']),
			);
			$query->update('#__jshopping_products_images')->set($fields)->where($conditions);

			$db->setQuery($query)->execute();
			unset($query);
		}

		/**
		 * Удаляем старые файлы
		 * Почему File::delete не отдали сразу массив?
		 * Пользователь может нажать "сохранить", а потом "сохранить и закрыть".
		 * Оба раза вызывается триггер onAfterSaveProductEnd. И во втором случае
		 * файлов уже нет (когда "Сохранить" - "Сохранить и закрыть") - выбрасывается исключение.
		 * Поэтому цикл с file_exists
		 */

		$delete_images = $session->get('wt_on_fly_image_handler_delete_images');
		$session->clear('wt_on_fly_image_handler_delete_images');
		foreach ($delete_images as $image)
		{
			if (file_exists($image))
			{
				File::delete($image);
			}
		}
	}

	/**
	 *
	 *  Контроллер config - Настройки JoomShopping - секция "Изображение"
	 *
	 * @param $view
	 *
	 *
	 * @since 1.0.0
	 * @see   \JshoppingControllerConfig::image
	 */
	public function onBeforeEditConfigImage(&$view)
	{

		if (!file_exists(JPATH_ADMINISTRATOR . '/manifests/libraries/jinterventionimage.xml'))
		{
			Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_NOT_FOUND') . ' ' . Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_DOWNLOAD_JINTERVENTION'), 'warning');
		}
		else
		{
			if (!LibraryHelper::isEnabled('jinterventionimage'))
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_DISABLED') . ' ' . Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ENABLE_JINTERVENTION'), 'warning');
			}
		}

		$view->tmp_html_start = Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ONBEFOREEDITCONFIGIMAGE_NOTICE');

	}

}


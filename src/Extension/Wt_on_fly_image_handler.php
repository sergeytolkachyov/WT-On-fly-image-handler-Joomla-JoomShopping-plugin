<?php
/**
 * @package       Jshoppingadmin - WT On fly image handler
 * @version       2.0.0
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2023 Sergey Tolkachyov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Jshoppingadmin\Wt_on_fly_image_handler\Extension;

use Intervention\Image\Image;
use Joomla\CMS\Helper\MediaHelper;
use Joomla\CMS\Language\Text;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\String\StringHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Filesystem\File;
use Joomla\CMS\Helper\LibraryHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Libraries\JInterventionimage\Manager;
use function dump;
use function file_exists;
use function is_null;
use function defined;

defined('_JEXEC') or die;

class Wt_on_fly_image_handler extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 *
	 * @return array
	 *
	 * @throws \Exception
	 * @since 4.1.0
	 *
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterSaveProductImage' => 'onAfterSaveProductImage',
			'onAfterSaveProductEnd'   => 'onAfterSaveProductEnd',
			'onBeforeEditConfigImage' => 'onBeforeEditConfigImage',
		];
	}

	/**
	 * User add images to product in admin panel and save product.
	 * Then product model store the product to database (JTable) and uploads and handles (resizes) images.
	 * We have to process the images after they have been processed by JoomShopping,
	 * so we have to delete JoomShopping files and resave our own after processing<br/>
	 * The trigger <strong>onAfterSaveProductImage</strong> fired on each image JoomShopping processing.<br/>
	 * The trigger <strong>onAfterSaveProductEnd</strong> fired once when product saved.
	 *
	 * @param Event $event
	 *
	 * @see   JshoppingModelProducts::save
	 * @see   JshoppingModelProducts::uploadImages
	 * @since 1.0.0
	 */
	public function onAfterSaveProductImage(Event $event)
	{
		/**
		 * @var int  $product_id  JoomShopping product id
		 * @var string  $name_image  product image name
		 */
		[$product_id, $name_image] = $event->getArguments();
		// Передаем имя файла в следующий триггер
		$session = $this->getApplication()->getSession();
		if ($session->get('wt_on_fly_image_handler_image_name'))
		{
			$image_names   = $session->get('wt_on_fly_image_handler_image_name');
			$delete_images = $session->get('wt_on_fly_image_handler_delete_images');
		}
		else
		{
			$image_names   = [];
			$delete_images = [];
		}
		/** @var string $name_thumb имя файла для миниатюры изображения */
		$name_thumb = 'thumb_' . $name_image;
		/** @var string $name_full имя файла исходного изображение */
		$name_full = 'full_' . $name_image;

		$jshopConfig = JSFactory::getConfig();

		/** @var string $path_image обработанное изображение */
		$path_image = $jshopConfig->image_product_path . "/" . $name_image;
		/** @var string $path_thumb миниатюра изображения */
		$path_thumb = $jshopConfig->image_product_path . "/" . $name_thumb;
		/** @var string $path_full исходное загруженное изображение */
		$path_full = $jshopConfig->image_product_path . "/" . $name_full;
		/** @var int $image_quality Image  quality from JoomShopping params */
		$image_quality = (int) $jshopConfig->image_quality;
		/** @var string $savepath Joomshopping image product path */
		$savepath = $jshopConfig->image_product_path;

		/**
		 * Переименовывать файлы изображений в человеко-понятные - название товара + числовой индекс
		 */

		if ($this->params->get('rename_images') == 1)
		{
			$product = JSFactory::getTable('product', 'jshop');
			$lang    = JSFactory::getLang();

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

		if ($name_image !== $new_file_name)
		{
			$delete_images[] = $path_image;
			$delete_images[] = $path_thumb;
			$delete_images[] = $path_full;
			$session->set('wt_on_fly_image_handler_delete_images', $delete_images);
		}

		// для оригиналов - показываются в лайтбоксе на фронте
		$image_original_width  = !empty($jshopConfig->image_product_original_width) ? (int) $jshopConfig->image_product_original_width : null;
		$image_original_height = !empty($jshopConfig->image_product_original_height) ? (int) $jshopConfig->image_product_original_height : null;

		$options = [
			'widthFit'        => $image_original_width,
			'heightFit'       => $image_original_height,
			'new_file_name'   => 'full_' . File::stripExt($new_file_name), // оригинал изображения
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $savepath,
			'image_quality'   => $image_quality,
		];

		// Изменяем оригинал изображения
		$this->handleImage($path_full, $options);

		// для среднего изображения - показываются в карточке товара на фронте

		$image_product_full_width  = !empty($jshopConfig->image_product_full_width) ? (int) $jshopConfig->image_product_full_width : null;
		$image_product_full_height = !empty($jshopConfig->image_product_full_height) ? (int) $jshopConfig->image_product_full_height : null;

		$options = [
			'widthFit'        => $image_product_full_width,
			'heightFit'       => $image_product_full_height,
			'new_file_name'   => File::stripExt($new_file_name), // основное изображение
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $savepath,
			'image_quality'   => $image_quality,
		];

		// Изменяем среднее изображение изображения
		$this->handleImage($path_full, $options);

		// для тумбочки изображения - показываются в карточке товара (зависит от шаблона) и в категории товаров на фронте

		$image_product_width  = !empty($jshopConfig->image_product_width) ? (int) $jshopConfig->image_product_width : null;
		$image_product_height = !empty($jshopConfig->image_product_height) ? (int) $jshopConfig->image_product_height : null;

		$options = [
			'widthFit'        => $image_product_width,
			'heightFit'       => $image_product_height,
			'new_file_name'   => 'thumb_' . File::stripExt($new_file_name), // тумбочка изображения
			'new_file_format' => $this->params->get('output_file_format', 'jpg'),
			'savepath'        => $savepath,
			'image_quality'   => $image_quality,
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
		/**
		 * 1. Изменяем размер изображения пропорционально
		 * 2. Дорисовываем поля до квадратных, если надо
		 * 3.
		 */
		$widthFit  = $options['widthFit'];
		$heightFit = $options['heightFit'];

		$manager = Manager::getInstance();

		$tmp_file = $manager->read($path_full);
		// https://github.com/Intervention/image/issues/551 Transparency on PNG issue
		$img = $manager->create($tmp_file->width(), $tmp_file->height())
			->fill('#ffffff');
		$img->place($tmp_file, 'center');
		// Убираем лишний фон и добавляем рамку в 50px вокруг, заливаем фоном из настроек
		if($this->params->get('trim_similar_color',0))
		{
			$img->trim($this->params->get('trim_similar_color_tolerance',20))
				->resizeCanvas(($tmp_file->width() + 50), ($tmp_file->height() + 50), $this->params->get('image_fill_color','#ffffff'));
		}

		//Делаем изображения квадратными
		if ($this->params->get('square_image', 0))
		{

			$width  = $img->width();
			$height = $img->height();
			$ratio  = $width / $height;

			if ($ratio != 1)
			{
				if ($width > $height)
				{
					//http://image.intervention.io/api/resizeCanvas
					$new_height = $width;
					$img->resizeCanvas($width, $new_height, $this->params->get('image_fill_color','#ffffff'), 'center');
				}
				elseif ($height > $width)
				{
					$new_width = $height;
					$img->resizeCanvas($new_width, $height, $this->params->get('image_fill_color','#ffffff'), 'center');
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
			$img->resize($widthFit, $heightFit);
		}

		$img->encodeByExtension($options['new_file_format'], quality: $options['image_quality']);
		$img = $this->placeWatermark($img);
		$img->save($options['savepath'] . '/' . $options['new_file_name'] . '.' . $options['new_file_format']);

	}

	/**
	 * @param   Image  $image  Intervention Image instance
	 *
	 * @return Image
	 *
	 * @since 2.0.0
	 */
	public function placeWatermark(Image $image): Image
	{
        $useWatermark = $this->params->get('watermark', 0);

        if (empty($useWatermark))
        {
            return $image;
        }

		$fileWatermark = $this->params->get('watermarkfile', '');

		if (empty($fileWatermark))
		{
			$this->getApplication()->enqueueMessage('WT On fly image handler: there is no watermark file specified');

			return $image;
		}

		$fileWatermark = MediaHelper::getCleanMediaFieldValue($fileWatermark);
		$fileWatermark = JPATH_SITE . '/' . $fileWatermark;
		$position      = $this->params->get('watermarkpos', 'bottom-right');
		$padding       = $this->params->get('watermarkpadding', 10);

		if (file_exists($fileWatermark))
		{

			$managerForWatermark = Manager::getInstance();
			$watermark           = $managerForWatermark->read($fileWatermark);

			$logoWidth   = $watermark->width();
			$logoHeight  = $watermark->height();
			$imageWidth  = $image->width();
			$imageHeight = $image->height();

			if ((int) $this->params->get('watermarkpercent', 0))
			{
				//сжимаем водяной знак по процентному соотношению от изображения на который накладывается
				$precent       = (double) $this->params->get('watermarkpercentvalue', 10);
				$logoWidthMax  = $imageWidth / 100 * $precent;
				$logoHeightMax = $imageHeight / 100 * $precent;
				$watermark->scale((int) $logoWidthMax, (int) $logoHeightMax);
			}

			if ($logoWidth > $imageWidth && $logoHeight > $imageHeight)
			{
				return $image;
			}

			$image->place($watermark, $position, $padding, $padding, $this->params->get('watermark_opacity',100));

			return $image;

		}
		$this->getApplication()->enqueueMessage('WT On fly image handler: watermark file not found in ' . $fileWatermark);

		return $image;

	}

	/**
	 * После обработки изображений нужно заменить имена файлов (расширения файлов) в БД
	 */

	public function onAfterSaveProductEnd(Event $event)
	{
		[$product_id] = $event->getArguments();

		$session     = $this->getApplication()->getSession();
		$image_names = $session->get('wt_on_fly_image_handler_image_name');
		$session->clear('wt_on_fly_image_handler_image_name');
		// В товаре - основное изображение
		$product = JSFactory::getTable('product');
		$product->load($product_id);
		// В таблице product_images
		$db = $this->getDatabase();

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
			$conditions = [
				$db->quoteName('image_name') . ' = ' . $db->quote($image_name['old_name']),
			];
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
	public function onBeforeEditConfigImage(Event $event): void
	{
		[$view] = $event->getArguments();
		if (!file_exists(JPATH_ADMINISTRATOR . '/manifests/libraries/jinterventionimage.xml'))
		{
			$this->getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_NOT_FOUND') . ' ' . Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_DOWNLOAD_JINTERVENTION'), 'warning');
		}
		else
		{
			if (!LibraryHelper::isEnabled('jinterventionimage'))
			{
				$this->getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_DISABLED') . ' ' . Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ENABLE_JINTERVENTION'), 'warning');
			}
		}

		$view->tmp_html_start .= Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ONBEFOREEDITCONFIGIMAGE_NOTICE');

	}
}

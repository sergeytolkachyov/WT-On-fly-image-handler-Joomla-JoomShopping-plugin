<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Helper\LibraryHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Version;
/**
 * Script file of HelloWorld component.
 *
 * The name of this class is dependent on the component being installed.
 * The class name should have the component's name, directly followed by
 * the text InstallerScript (ex:. com_helloWorldInstallerScript).
 *
 * This class will be called by Joomla!'s installer, if specified in your component's
 * manifest file, and is used for custom automation actions in its installation process.
 *
 * In order to use this automation script, you should reference it in your component's
 * manifest file as follows:
 * <scriptfile>script.php</scriptfile>
 *
 * @package     Joomla.Administrator
 * @subpackage  com_helloworld
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
class plgJshoppingadminWt_on_fly_image_handlerInstallerScript
{
    /**
     * This method is called after a component is installed.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function install($parent)
    {

    }

    /**
     * This method is called after a component is uninstalled.
     *
     * @param  \stdClass $parent - Parent object calling this method.
     *
     * @return void
     */
    public function uninstall($parent) 
    {

		
    }

    /**
     * This method is called after a component is updated.
     *
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function update($parent) 
    {

    }

    /**
     * Runs just before any installation action is performed on the component.
     * Verifications and pre-requisites should run in this function.
     *
     * @param  string    $type   - Type of PreFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $parent - Parent object calling object.
     *
     * @return void
     */
    public function preflight($type, $parent) 
    {
		
		$lib_jinterventionimage_url = 'https://web-tolk.ru/get.html?element=lib_jinterventionimage';
		$lib_jinterventionimage_url_2 = 'https://hika.su/update/free/lib_jinterventionimage.zip';
		$response = HttpFactory::getHttp()->get($lib_jinterventionimage_url);
		if($response->code == 200){
			
			if($this->installDependencies($parent, $lib_jinterventionimage_url) == true){
				
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_DOWNLOAD_OK'), 'success');
				
			} else {
				
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_DOWNLOAD_FAILED'), 'error');
				
			}
			
		} else {

			// 2nd server
			$response2 = HttpFactory::getHttp()->get($lib_jinterventionimage_url_2);
			if($response2->code == 200){

				if($this->installDependencies($parent, $lib_jinterventionimage_url_2) == true){
					
						Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_DOWNLOAD_OK'), 'success');
						
					} else {
						
						Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_DOWNLOAD_FAILED'), 'error');
						
					}
				
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_DOWNLOAD_FAILED'), 'error');
			
			}
			
		}

    }
	/**
	 * @param $parent
	 *
	 * @return bool
	 * @throws Exception
	 *
	 *
	 * @since 1.0.0
	 */
	protected function installDependencies($parent,$url)
	{
		// Load installer plugins for assistance if required:
		PluginHelper::importPlugin('installer');

		$app = Factory::getApplication();

		$package = null;

		// This event allows an input pre-treatment, a custom pre-packing or custom installation.
		// (e.g. from a JSON description).
		$results = $app->triggerEvent('onInstallerBeforeInstallation', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			return false;
		}



		// Download the package at the URL given.
		$p_file = InstallerHelper::downloadPackage($url);

		// Was the package downloaded?
		if (!$p_file)
		{
			$app->enqueueMessage(Text::_('COM_INSTALLER_MSG_INSTALL_INVALID_URL'), 'error');

			return false;
		}

		$config   = Factory::getConfig();
		$tmp_dest = $config->get('tmp_path');

		// Unpack the downloaded package file.
		$package = InstallerHelper::unpack($tmp_dest . '/' . $p_file, true);

		// This event allows a custom installation of the package or a customization of the package:
		$results = $app->triggerEvent('onInstallerBeforeInstaller', array($this, &$package));

		if (in_array(true, $results, true))
		{
			return true;
		}

		if (in_array(false, $results, true))
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

			return false;
		}

		// Get an installer instance.
		$installer = new Installer();

		/*
		 * Check for a Joomla core package.
		 * To do this we need to set the source path to find the manifest (the same first step as JInstaller::install())
		 *
		 * This must be done before the unpacked check because JInstallerHelper::detectType() returns a boolean false since the manifest
		 * can't be found in the expected location.
		 */
		if (is_array($package) && isset($package['dir']) && is_dir($package['dir']))
		{
			$installer->setPath('source', $package['dir']);

			if (!$installer->findManifest())
			{
				InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
				$app->enqueueMessage(Text::sprintf('COM_INSTALLER_INSTALL_ERROR', '.'), 'warning');

				return false;
			}
		}

		// Was the package unpacked?
		if (!$package || !$package['type'])
		{
			InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
			$app->enqueueMessage(Text::_('COM_INSTALLER_UNABLE_TO_FIND_INSTALL_PACKAGE'), 'error');

			return false;
		}

		// Install the package.
		if (!$installer->install($package['dir']))
		{
			// There was an error installing the package.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_ERROR',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = false;
			$msgType = 'error';
		}
		else
		{
			// Package installed successfully.
			$msg     = Text::sprintf('COM_INSTALLER_INSTALL_SUCCESS',
				Text::_('COM_INSTALLER_TYPE_TYPE_' . strtoupper($package['type'])));
			$result  = true;
			$msgType = 'message';
		}

		// This event allows a custom a post-flight:
		$app->triggerEvent('onInstallerAfterInstaller', array($parent, &$package, $installer, &$result, &$msg));

		$app->enqueueMessage($msg, $msgType);

		// Cleanup the install files.
		if (!is_file($package['packagefile']))
		{
			$package['packagefile'] = $config->get('tmp_path') . '/' . $package['packagefile'];
		}

		InstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;
	}


    /**
     * Runs right after any installation action is performed on the component.
     *
     * @param  string    $type   - Type of PostFlight action. Possible values are:
     *                           - * install
     *                           - * update
     *                           - * discover_install
     * @param  \stdClass $installer - Parent object calling object.
     *
     * @return void
     */
    function postflight($type, $installer) 
    {

	
		$jversion = new Version();
			
		// only for Joomla 3.x

		if (version_compare($jversion->getShortVersion(), '4.0', '<')) {
			
			$element = strtoupper($installer->get("element")); // ex. "$parent"
			$class = 'span';
			$web_tolk_site_icon = "<i class='icon-share-alt'></i>";

		} else {
			
			$element = strtoupper($installer->getElement());
			$class = 'col-';
			$web_tolk_site_icon = '';
		}
	
	
	if (!file_exists(JPATH_ADMINISTRATOR . '/manifests/libraries/jinterventionimage.xml'))
		{
			Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_NOT_FOUND').' '.Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_DOWNLOAD_JINTERVENTION'), 'warning');
		}
		else
		{
			if (!LibraryHelper::isEnabled('jinterventionimage'))
			{
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_DISABLED').' '.Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ENABLE_JINTERVENTION'), 'warning');
			} else {
				Factory::getApplication()->enqueueMessage(Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_JINTERVENTION_STATUS_ENABLED'), 'success');
			}
		}
	
	$jinterventionimage = '<div class="alert '.$class.'12" style="background-color: #2A384D; display: flex; align-items: center;">';
		$jinterventionimage .= '<a href="https://intervention.io" style="margin-right: 20px;" "><svg class="icon" width="215" height="40" viewBox="0 0 432 80" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M26.6127 22.4737C24.0191 26.4056 19.5625 29 14.5 29C6.49187 29 0 22.5081 0 14.5C0 6.49187 6.49187 0 14.5 0C19.7151 0 24.2871 2.75314 26.842 6.88526H56.5L71 32L59.453 52H46.4241L44.2592 48.2502L54 32L48.5 22.4737H26.6127Z" fill="white"></path>
					<path fill-rule="evenodd" clip-rule="evenodd" d="M64.3873 57.5263C66.9809 53.5944 71.4375 51 76.5 51C84.5081 51 91 57.4919 91 65.5C91 73.5081 84.5081 80 76.5 80C71.2849 80 66.7129 77.2469 64.158 73.1147H34.5L20 48L31.547 28H44.5759L46.7408 31.7498L37 48L42.5 57.5263L64.3873 57.5263Z" fill="#7F91B6"></path>
					<path d="M114.418 22.218V15.522H123.408V22.218H114.418ZM123.222 57H114.604V26.31H123.222V57ZM141.863 26.31H143.413C148.249 26.31 150.667 28.604 150.667 33.192V57H141.987V34.37C141.987 33.3367 141.429 32.82 140.313 32.82H138.825C137.626 32.82 137.027 33.4813 137.027 34.804V57H128.409V26.31H136.903V29.782C137.481 27.4673 139.135 26.31 141.863 26.31ZM169.233 32.82H165.203V48.878C165.203 49.87 165.761 50.366 166.877 50.366H169.233V57H164.397C159.189 57 156.585 54.706 156.585 50.118V32.82H153.361V26.31H156.585V20.606H165.203V26.31H169.233V32.82ZM179.865 26.31H186.871C192.12 26.31 194.745 28.604 194.745 33.192V44.042H180.299V49.312C180.299 49.8493 180.423 50.242 180.671 50.49C180.919 50.6967 181.353 50.8 181.973 50.8H184.763C185.341 50.8 185.755 50.676 186.003 50.428C186.251 50.18 186.375 49.7667 186.375 49.188V47.142H194.683V50.118C194.683 54.706 192.058 57 186.809 57H179.865C174.657 57 172.053 54.706 172.053 50.118V33.192C172.053 28.604 174.657 26.31 179.865 26.31ZM180.299 38.958H186.437V33.998C186.437 33.4607 186.313 33.0887 186.065 32.882C185.817 32.634 185.403 32.51 184.825 32.51H181.973C181.353 32.51 180.919 32.634 180.671 32.882C180.423 33.0887 180.299 33.4607 180.299 33.998V38.958ZM213.575 26.124H214.629V33.626H211.591C208.987 33.626 207.685 35.0727 207.685 37.966V57H199.067V26.31H207.561V30.712C207.892 29.348 208.574 28.2527 209.607 27.426C210.64 26.558 211.963 26.124 213.575 26.124ZM223.672 26.31L227.516 48.32L231.36 26.31H239.978L232.91 57H222.06L215.054 26.31H223.672ZM250.039 26.31H257.045C262.294 26.31 264.919 28.604 264.919 33.192V44.042H250.473V49.312C250.473 49.8493 250.597 50.242 250.845 50.49C251.093 50.6967 251.527 50.8 252.147 50.8H254.937C255.515 50.8 255.929 50.676 256.177 50.428C256.425 50.18 256.549 49.7667 256.549 49.188V47.142H264.857V50.118C264.857 54.706 262.232 57 256.983 57H250.039C244.831 57 242.227 54.706 242.227 50.118V33.192C242.227 28.604 244.831 26.31 250.039 26.31ZM250.473 38.958H256.611V33.998C256.611 33.4607 256.487 33.0887 256.239 32.882C255.991 32.634 255.577 32.51 254.999 32.51H252.147C251.527 32.51 251.093 32.634 250.845 32.882C250.597 33.0887 250.473 33.4607 250.473 33.998V38.958ZM282.695 26.31H284.245C289.081 26.31 291.499 28.604 291.499 33.192V57H282.819V34.37C282.819 33.3367 282.261 32.82 281.145 32.82H279.657C278.458 32.82 277.859 33.4813 277.859 34.804V57H269.241V26.31H277.735V29.782C278.313 27.4673 279.967 26.31 282.695 26.31ZM310.065 32.82H306.035V48.878C306.035 49.87 306.593 50.366 307.709 50.366H310.065V57H305.229C300.021 57 297.417 54.706 297.417 50.118V32.82H294.193V26.31H297.417V20.606H306.035V26.31H310.065V32.82ZM313.133 22.218V15.522H322.123V22.218H313.133ZM321.937 57H313.319V26.31H321.937V57ZM334.44 26.31H341.818C344.546 26.31 346.488 26.9093 347.646 28.108C348.844 29.2653 349.444 30.96 349.444 33.192V50.118C349.444 52.35 348.844 54.0653 347.646 55.264C346.488 56.4213 344.546 57 341.818 57H334.44C331.712 57 329.748 56.4213 328.55 55.264C327.392 54.0653 326.814 52.35 326.814 50.118V33.192C326.814 30.96 327.392 29.2653 328.55 28.108C329.748 26.9093 331.712 26.31 334.44 26.31ZM340.95 49.25V34.06C340.95 33.0267 340.392 32.51 339.276 32.51H336.982C335.866 32.51 335.308 33.0267 335.308 34.06V49.25C335.308 50.2833 335.866 50.8 336.982 50.8H339.276C340.392 50.8 340.95 50.2833 340.95 49.25ZM367.824 26.31H369.374C374.21 26.31 376.628 28.604 376.628 33.192V57H367.948V34.37C367.948 33.3367 367.39 32.82 366.274 32.82H364.786C363.587 32.82 362.988 33.4813 362.988 34.804V57H354.37V26.31H362.864V29.782C363.442 27.4673 365.096 26.31 367.824 26.31ZM381.306 57V47.824H390.296V57H381.306ZM394.992 22.218V15.522H403.982V22.218H394.992ZM403.796 57H395.178V26.31H403.796V57ZM416.299 26.31H423.677C426.405 26.31 428.348 26.9093 429.505 28.108C430.704 29.2653 431.303 30.96 431.303 33.192V50.118C431.303 52.35 430.704 54.0653 429.505 55.264C428.348 56.4213 426.405 57 423.677 57H416.299C413.571 57 411.608 56.4213 410.409 55.264C409.252 54.0653 408.673 52.35 408.673 50.118V33.192C408.673 30.96 409.252 29.2653 410.409 28.108C411.608 26.9093 413.571 26.31 416.299 26.31ZM422.809 49.25V34.06C422.809 33.0267 422.251 32.51 421.135 32.51H418.841C417.725 32.51 417.167 33.0267 417.167 34.06V49.25C417.167 50.2833 417.725 50.8 418.841 50.8H421.135C422.251 50.8 422.809 50.2833 422.809 49.25Z" fill="white"></path>
					</svg>
					</a>';

		if(!file_exists(JPATH_ADMINISTRATOR.'/manifests/libraries/jinterventionimage.xml')){
			$jinterventionimage .= Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_DOWNLOAD_JINTERVENTION');
		} else {
			if (!LibraryHelper::isEnabled('jinterventionimage'))
			{
				$jinterventionimage .= Text::_('PLG_WT_ON_FLY_IMAGE_HANDLER_ENABLE_JINTERVENTION');
			} else {
				$jintervention = simplexml_load_file(JPATH_ADMINISTRATOR.'/manifests/libraries/jinterventionimage.xml');
				$jinterventionimage .= '<span class="badge badge-success bg-success" style="margin-right: 10px;">v.'.$jintervention->version.'</span> ';
				$jinterventionimage .= '<a class="badge badge-success bg-success" style="margin-right: 10px;" href="https://hika.su/zagruzki" target="_blank">Co-Author</a>';
				$jinterventionimage .= '<a class="badge badge-success bg-success" style="margin-right: 10px;" href="https://github.com/Delo-Design/jinterventionimage" target="_blank">GitHub</a>';
				$jinterventionimage .= '<a class="badge badge-success bg-success" style="margin-right: 10px;" href="https://github.com/Intervention/image" target="_blank">Original library GitHub</a>';
			}

		}
		$jinterventionimage .= '</div>';
	
	
	
	
		echo "
		<div class='row bg-white' style='margin:25px auto; border:1px solid rgba(0,0,0,0.125); box-shadow:0px 0px 10px rgba(0,0,0,0.125); padding: 10px 20px;'>
		<div class='".$class."8 p-2'>
		<h2>".Text::_("PLG_".$element."_AFTER_".strtoupper($type))." <br/>".Text::_("PLG_".$element)."</h2>
		".Text::_("PLG_".$element."_DESC");
		
		
		echo Text::_("PLG_".$element."_WHATS_NEW");
	
		echo $jinterventionimage;	
		
		echo "</div>
		<div class='".$class."4' style='display:flex; flex-direction:column; justify-content:center;'>
		<img width='200px' src='https://web-tolk.ru/web_tolk_logo_wide.png'>
		<p>Joomla Extensions</p>
		<p class='btn-group'>
			<a class='btn btn-sm btn-outline-primary' href='https://web-tolk.ru' target='_blank'>".$web_tolk_site_icon." https://web-tolk.ru</a>
			<a class='btn btn-sm btn-outline-primary' href='mailto:info@web-tolk.ru'><i class='icon-envelope'></i> info@web-tolk.ru</a>
		</p>
		<p><a class='btn' href='https://t.me/joomlaru' target='_blank'>Joomla Russian Community in Telegram</a></p>
		".Text::_("PLG_".$element."_MAYBE_INTERESTING")."
		</div>


		";		
	
    }
}
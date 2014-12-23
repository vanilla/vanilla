<?php if (!defined("APPLICATION")) exit();
/*
 *  Vanoogle vanilla plugin.
 *  Copyright (C) 2011 ddumont@gmail.com
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>. 
 */

$PluginInfo["Vanoogle"] = array(
	"Name" => "Vanoogle",
	"Description" => "A google search plugin for vanilla 2+",
	"Version" => "1.2",
	"Author" => "Dan Dumont",
	"AuthorEmail" => "ddumont@gmail.com",
	"SettingsUrl" => "/dashboard/settings/vanoogle",
	"SettingsPermission" => "Garden.Settings.Manage",
	"AuthorUrl" => "http://blog.canofsleep.com",
	"RequiredApplications" => array("Vanilla" => "2.0.17") // This needs to be bumped when Vanilla releases with my contributed changes
);

/**
 * Vanoogle seearch plugin for Vanilla
 * @author ddumont@gmail.com
 */
class Vanoogle extends Gdn_Plugin {	
	
	/**
	 * Build the setting page.
	 * @param $Sender
	 */
	public function SettingsController_Vanoogle_Create($Sender) {
		$Sender->Permission('Garden.Settings.Manage');
		
		$Validation = new Gdn_Validation();
		$ConfigurationModel = new Gdn_ConfigurationModel($Validation);
		$ConfigurationModel->SetField(array("Plugins.Vanoogle.CSE"));
		$Sender->Form->SetModel($ConfigurationModel);

		if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
			$Sender->Form->SetData($ConfigurationModel->Data);
		} else {
        	$Data = $Sender->Form->FormValues(); 
			$ConfigurationModel->Validation->ApplyRule("Plugins.Vanoogle.CSE", "Required"); 
        	if ($Sender->Form->Save() !== FALSE)
        		$Sender->StatusMessage = T("Your settings have been saved.");
		}
		
		$Sender->AddSideMenu();
		$Sender->SetData("Title", T("Vanoogle Settings"));
		
		$CategoryModel = new CategoryModel();
		$Sender->SetData("CategoryData", $CategoryModel->GetAll(), TRUE);
		array_shift($Sender->CategoryData->Result());
		
		$Sender->Render($this->GetView("settings.php"));
	}	
	
	/**
	 * Add our script and css to every page.
	 * 
	 * @param $Sender
	 */
	public function Base_Render_Before($Sender) {
		if (!C("Plugins.Vanoogle.CSE"))
			return;
			
	    // Normally one would use ->AddJsFile or ->Head->AddScript, but these insert a version arg in the url that makes the google api barf.
        $Sender->Head->AddTag('script', array(
        	'src' => Asset('https://www.google.com/jsapi', FALSE, FALSE),
        	'type' => 'text/javascript',
        	'id' => C("Plugins.Vanoogle.CSE")
        ));
		$Sender->AddCssFile('vanoogle.css', 'plugins/Vanoogle');
		$Sender->AddJsFile('vanoogle.js', 'plugins/Vanoogle');
	}
	
	/**
	 * Place our search element on page to be moved by js later.
	 * 
	 * @param $Sender
	 */
	public function Base_Render_After($Sender) {
		if (!C("Plugins.Vanoogle.ApiKey"))
			return;
		?>
			<div id="hidden" style="display:none;">
				<div id="VanoogleSearch"><?php echo T('Loading Search...');?></div>
				<div id="vanoogle_webResult">
					<li class="Item gs-webResult gs-result" 
					  data-vars="{longUrl:function(){var i = unescapedUrl.indexOf(visibleUrl); return i &lt; 1 ? visibleUrl : unescapedUrl.substring(i);},trimmedTitle:function(){return html(title.replace(/[-][^-]+$/, ''));}}">
						<div class="ItemContent">
							<div data-if="Vars.richSnippet" data-attr="0" data-body="render('thumbnail',richSnippet,{url:unescapedUrl,target:target})"></div>
							<div>
								<a class="Title" data-attr="{href:unescapedUrl,target:target,dir:bidiHtmlDir(title)}" data-body="trimmedTitle()"></a>
							</div>
							<div class="Message gs-bidi-start-align gs-snippet" style="margin:0;padding-left:10px" data-body="html(content)" data-attr="{dir:bidiHtmlDir(content)}"></div>
						</div>
					</li>
				</div>
				<div id="vanoogle_thumbnail">
					<div data-attr="0" data-vars="{tn:(Vars.thumbnail &amp;&amp; thumbnail.src) ? thumbnail : ( (Vars.cse_thumbnail &amp;&amp; cse_thumbnail.src) ? cse_thumbnail : {src:Vars.document &amp;&amp; document.thumbnailUrl})}">
						<div data-if="tn.src">
							<a class="gs-image" data-attr="{href:url,target:target}">
								<img style="display: none;" class="gs-image" data-attr="{src:tn.src}" onload="this.style.display = 'inline'; if (this.parentNode &amp;&amp; this.parentNode.parentNode) { this.parentNode.parentNode.className = 'gs-image-box gs-web-image-box'; } ">
							</a>
						</div>
					</div>
				</div>
				<div id="vanoogle_action">
					<div data-foreach="Vars.action" data-attr="0">
						<div data-attr="{'class': 'gs-action ' + Cur['class']}" data-if="Cur.url &amp;&amp; Cur.label">
							<a class="gs-action" data-attr="{href:Cur.url,target:target,dir:bidiTextDir(Cur.label)}" data-body="Cur.label"></a>
						</div>
					</div>
				</div>
			</div>
		<?php 
	}
   
	public function Setup() {}
}
<?php
/**
 * @link http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license http://craftcms.com/license
 */

namespace craft\behaviors;

use yii\base\Behavior;

/**
 * Custom field behavior
 *
 * This class provides attributes for all the unique custom field handles.
 *
 * @method static localPhoneNumber(mixed $value) Sets the [[localPhoneNumber]] property
 * @method static headerHTML(mixed $value) Sets the [[headerHTML]] property
 * @method static footerHTML(mixed $value) Sets the [[footerHTML]] property
 * @method static featuredImage(mixed $value) Sets the [[featuredImage]] property
 * @method static excerpt(mixed $value) Sets the [[excerpt]] property
 * @method static entryContent(mixed $value) Sets the [[entryContent]] property
 * @method static accordionHeadline(mixed $value) Sets the [[accordionHeadline]] property
 * @method static accordionContent(mixed $value) Sets the [[accordionContent]] property
 * @method static blockquoteContent(mixed $value) Sets the [[blockquoteContent]] property
 * @method static blockquotePosition(mixed $value) Sets the [[blockquotePosition]] property
 * @method static buttonLabel(mixed $value) Sets the [[buttonLabel]] property
 * @method static buttonLink(mixed $value) Sets the [[buttonLink]] property
 * @method static customCode(mixed $value) Sets the [[customCode]] property
 * @method static embeddedVideoSource(mixed $value) Sets the [[embeddedVideoSource]] property
 * @method static contentBlock(mixed $value) Sets the [[contentBlock]] property
 * @method static image(mixed $value) Sets the [[image]] property
 * @method static imageCaption(mixed $value) Sets the [[imageCaption]] property
 * @method static imagePosition(mixed $value) Sets the [[imagePosition]] property
 * @method static imageGalleryTitle(mixed $value) Sets the [[imageGalleryTitle]] property
 * @method static imageGalleryImages(mixed $value) Sets the [[imageGalleryImages]] property
 * @method static accessibilityText(mixed $value) Sets the [[accessibilityText]] property
 * @method static pullquoteContent(mixed $value) Sets the [[pullquoteContent]] property
 * @method static pullquoteAttribution(mixed $value) Sets the [[pullquoteAttribution]] property
 * @method static form(mixed $value) Sets the [[form]] property
 * @method static buttonStyle(mixed $value) Sets the [[buttonStyle]] property
 * @method static caption(mixed $value) Sets the [[caption]] property
 * @method static imageGalleryIntroContent(mixed $value) Sets the [[imageGalleryIntroContent]] property
 * @method static formIntroContent(mixed $value) Sets the [[formIntroContent]] property
 * @method static externalLink(mixed $value) Sets the [[externalLink]] property
 * @method static metaSeoData(mixed $value) Sets the [[metaSeoData]] property
 * @method static primaryNavigation(mixed $value) Sets the [[primaryNavigation]] property
 * @method static navLink(mixed $value) Sets the [[navLink]] property
 * @method static auxNavigation(mixed $value) Sets the [[auxNavigation]] property
 * @method static socialNetworks(mixed $value) Sets the [[socialNetworks]] property
 * @method static socialNetworkName(mixed $value) Sets the [[socialNetworkName]] property
 * @method static socialNetworkLink(mixed $value) Sets the [[socialNetworkLink]] property
 * @method static pullquotePosition(mixed $value) Sets the [[pullquotePosition]] property
 * @method static calloutLink(mixed $value) Sets the [[calloutLink]] property
 * @method static calloutBlockHeadline(mixed $value) Sets the [[calloutBlockHeadline]] property
 * @method static calloutBlockContent(mixed $value) Sets the [[calloutBlockContent]] property
 * @method static calloutBlockLink(mixed $value) Sets the [[calloutBlockLink]] property
 * @method static embeddedVideoPosition(mixed $value) Sets the [[embeddedVideoPosition]] property
 * @method static newsCategory(mixed $value) Sets the [[newsCategory]] property
 * @method static blogCategory(mixed $value) Sets the [[blogCategory]] property
 * @method static calloutBlockImage(mixed $value) Sets the [[calloutBlockImage]] property
 * @method static downloadCalloutTitle(mixed $value) Sets the [[downloadCalloutTitle]] property
 * @method static downloadCalloutLink(mixed $value) Sets the [[downloadCalloutLink]] property
 * @method static showChildPages(mixed $value) Sets the [[showChildPages]] property
 * @method static imageLink(mixed $value) Sets the [[imageLink]] property
 * @method static positionTitle(mixed $value) Sets the [[positionTitle]] property
 * @method static employmentType(mixed $value) Sets the [[employmentType]] property
 * @method static baseSalary(mixed $value) Sets the [[baseSalary]] property
 * @method static baseSalaryMinimumValue(mixed $value) Sets the [[baseSalaryMinimumValue]] property
 * @method static baseSalaryMaximumValue(mixed $value) Sets the [[baseSalaryMaximumValue]] property
 * @method static baseSalaryUnit(mixed $value) Sets the [[baseSalaryUnit]] property
 * @method static educationRequirements(mixed $value) Sets the [[educationRequirements]] property
 * @method static experienceRequirements(mixed $value) Sets the [[experienceRequirements]] property
 * @method static industry(mixed $value) Sets the [[industry]] property
 * @method static qualifications(mixed $value) Sets the [[qualifications]] property
 * @method static responsibilities(mixed $value) Sets the [[responsibilities]] property
 * @method static skills(mixed $value) Sets the [[skills]] property
 * @method static workHours(mixed $value) Sets the [[workHours]] property
 * @method static sidebarWidgets(mixed $value) Sets the [[sidebarWidgets]] property
 * @method static calloutHeadline(mixed $value) Sets the [[calloutHeadline]] property
 * @method static calloutImage(mixed $value) Sets the [[calloutImage]] property
 * @method static calloutExcerpt(mixed $value) Sets the [[calloutExcerpt]] property
 * @method static imageAdImage(mixed $value) Sets the [[imageAdImage]] property
 * @method static imageAdLink(mixed $value) Sets the [[imageAdLink]] property
 * @method static sidebarWidgetsSelection(mixed $value) Sets the [[sidebarWidgetsSelection]] property
 * @method static showSubnavigation(mixed $value) Sets the [[showSubnavigation]] property
 * @method static callouts(mixed $value) Sets the [[callouts]] property
 * @method static calloutsHeadline(mixed $value) Sets the [[calloutsHeadline]] property
 * @method static downloadCalloutExcerpt(mixed $value) Sets the [[downloadCalloutExcerpt]] property
 * @method static disclaimerBlock(mixed $value) Sets the [[disclaimerBlock]] property
 * @method static productCategory(mixed $value) Sets the [[productCategory]] property
 * @method static make(mixed $value) Sets the [[make]] property
 * @method static model(mixed $value) Sets the [[model]] property
 * @method static replacesOEM(mixed $value) Sets the [[replacesOEM]] property
 * @method static part(mixed $value) Sets the [[part]] property
 * @method static customSchematicIdentifierList(mixed $value) Sets the [[customSchematicIdentifierList]] property
 * @method static fax(mixed $value) Sets the [[fax]] property
 * @method static footerLinks(mixed $value) Sets the [[footerLinks]] property
 * @method static relatedProducts(mixed $value) Sets the [[relatedProducts]] property
 * @method static secondaryCalloutLink(mixed $value) Sets the [[secondaryCalloutLink]] property
 * @method static calloutShowBottomBar(mixed $value) Sets the [[calloutShowBottomBar]] property
 * @method static imageAdShowBottomBar(mixed $value) Sets the [[imageAdShowBottomBar]] property
 * @method static newProduct(mixed $value) Sets the [[newProduct]] property
 * @method static calloutsImage(mixed $value) Sets the [[calloutsImage]] property
 * @method static calloutsExcerpt(mixed $value) Sets the [[calloutsExcerpt]] property
 * @method static calloutsPrimaryButton(mixed $value) Sets the [[calloutsPrimaryButton]] property
 * @method static calloutsSecondaryButton(mixed $value) Sets the [[calloutsSecondaryButton]] property
 * @method static featuredParts(mixed $value) Sets the [[featuredParts]] property
 * @method static introContent(mixed $value) Sets the [[introContent]] property
 * @method static introContentHeadline(mixed $value) Sets the [[introContentHeadline]] property
 * @method static introContentExcerpt(mixed $value) Sets the [[introContentExcerpt]] property
 * @method static introContentButton(mixed $value) Sets the [[introContentButton]] property
 * @method static heroImage(mixed $value) Sets the [[heroImage]] property
 * @method static categoryIcon(mixed $value) Sets the [[categoryIcon]] property
 * @method static featuredCategory(mixed $value) Sets the [[featuredCategory]] property
 * @method static relatedSchematics(mixed $value) Sets the [[relatedSchematics]] property
 * @method static productDescription(mixed $value) Sets the [[productDescription]] property
 * @method static imageGallery(mixed $value) Sets the [[imageGallery]] property
 * @method static relatedResources(mixed $value) Sets the [[relatedResources]] property
 * @method static internalUseCompatibleModels(mixed $value) Sets the [[internalUseCompatibleModels]] property
 * @method static orderNotes(mixed $value) Sets the [[orderNotes]] property
 * @method static businessName(mixed $value) Sets the [[businessName]] property
 * @method static contactPhone(mixed $value) Sets the [[contactPhone]] property
 * @method static defaultQuantity(mixed $value) Sets the [[defaultQuantity]] property
 * @method static jdfAccountNumber(mixed $value) Sets the [[jdfAccountNumber]] property
 * @method static relatedProductsHeadline(mixed $value) Sets the [[relatedProductsHeadline]] property
 * @method static schematicOrder(mixed $value) Sets the [[schematicOrder]] property
 * @method static featuredMakes(mixed $value) Sets the [[featuredMakes]] property
 * @method static makeDisclaimer(mixed $value) Sets the [[makeDisclaimer]] property
 * @method static howDidYouHearAboutKooimaCompany(mixed $value) Sets the [[howDidYouHearAboutKooimaCompany]] property
 * @method static subscribeToNewsletter(mixed $value) Sets the [[subscribeToNewsletter]] property
 * @method static modelDisclaimer(mixed $value) Sets the [[modelDisclaimer]] property
 * @method static featureFlag(mixed $value) Sets the [[featureFlag]] property
 * @method static rating(mixed $value) Sets the [[rating]] property
 * @method static reviewTitle(mixed $value) Sets the [[reviewTitle]] property
 * @method static lastNameInitial(mixed $value) Sets the [[lastNameInitial]] property
 * @method static productSku(mixed $value) Sets the [[productSku]] property
 * @method static updateProduct(mixed $value) Sets the [[updateProduct]] property
 * @method static phone(mixed $value) Sets the [[phone]] property
 * @method static salesDocType(mixed $value) Sets the [[salesDocType]] property
 * @method static salespadCustomerNumber(mixed $value) Sets the [[salespadCustomerNumber]] property
 * @method static responseTranscript(mixed $value) Sets the [[responseTranscript]] property
 * @method static requestTranscript(mixed $value) Sets the [[requestTranscript]] property
 * @method static salesDocNum(mixed $value) Sets the [[salesDocNum]] property
 * @method static heroHeading(mixed $value) Sets the [[heroHeading]] property
 * @method static announcementHeading(mixed $value) Sets the [[announcementHeading]] property
 * @method static homepageContent(mixed $value) Sets the [[homepageContent]] property
 * @method static announcementColorTheme(mixed $value) Sets the [[announcementColorTheme]] property
 * @method static showAnnouncement(mixed $value) Sets the [[showAnnouncement]] property
 * @method static announcementSummary(mixed $value) Sets the [[announcementSummary]] property
 * @method static partSelectorLink(mixed $value) Sets the [[partSelectorLink]] property
 * @method static partSelectorHeading(mixed $value) Sets the [[partSelectorHeading]] property
 * @method static announcementLink(mixed $value) Sets the [[announcementLink]] property
 * @method static headingLink(mixed $value) Sets the [[headingLink]] property
 * @method static heading(mixed $value) Sets the [[heading]] property
 * @method static products(mixed $value) Sets the [[products]] property
 * @method static summary(mixed $value) Sets the [[summary]] property
 * @method static primaryButton(mixed $value) Sets the [[primaryButton]] property
 * @method static secondaryButton(mixed $value) Sets the [[secondaryButton]] property
 * @method static ctaLink(mixed $value) Sets the [[ctaLink]] property
 */
class CustomFieldBehavior extends Behavior
{
    /**
     * @var bool Whether the behavior should provide methods based on the field handles.
     */
    public bool $hasMethods = false;

    /**
     * @var bool Whether properties on the class should be settable directly.
     */
    public bool $canSetProperties = true;

    /**
     * @var array<string,bool> List of supported field handles.
     */
    public static $fieldHandles = [
        'localPhoneNumber' => true,
        'headerHTML' => true,
        'footerHTML' => true,
        'featuredImage' => true,
        'excerpt' => true,
        'entryContent' => true,
        'accordionHeadline' => true,
        'accordionContent' => true,
        'blockquoteContent' => true,
        'blockquotePosition' => true,
        'buttonLabel' => true,
        'buttonLink' => true,
        'customCode' => true,
        'embeddedVideoSource' => true,
        'contentBlock' => true,
        'image' => true,
        'imageCaption' => true,
        'imagePosition' => true,
        'imageGalleryTitle' => true,
        'imageGalleryImages' => true,
        'accessibilityText' => true,
        'pullquoteContent' => true,
        'pullquoteAttribution' => true,
        'form' => true,
        'buttonStyle' => true,
        'caption' => true,
        'imageGalleryIntroContent' => true,
        'formIntroContent' => true,
        'externalLink' => true,
        'metaSeoData' => true,
        'primaryNavigation' => true,
        'navLink' => true,
        'auxNavigation' => true,
        'socialNetworks' => true,
        'socialNetworkName' => true,
        'socialNetworkLink' => true,
        'pullquotePosition' => true,
        'calloutLink' => true,
        'calloutBlockHeadline' => true,
        'calloutBlockContent' => true,
        'calloutBlockLink' => true,
        'embeddedVideoPosition' => true,
        'newsCategory' => true,
        'blogCategory' => true,
        'calloutBlockImage' => true,
        'downloadCalloutTitle' => true,
        'downloadCalloutLink' => true,
        'showChildPages' => true,
        'imageLink' => true,
        'positionTitle' => true,
        'employmentType' => true,
        'baseSalary' => true,
        'baseSalaryMinimumValue' => true,
        'baseSalaryMaximumValue' => true,
        'baseSalaryUnit' => true,
        'educationRequirements' => true,
        'experienceRequirements' => true,
        'industry' => true,
        'qualifications' => true,
        'responsibilities' => true,
        'skills' => true,
        'workHours' => true,
        'sidebarWidgets' => true,
        'calloutHeadline' => true,
        'calloutImage' => true,
        'calloutExcerpt' => true,
        'imageAdImage' => true,
        'imageAdLink' => true,
        'sidebarWidgetsSelection' => true,
        'showSubnavigation' => true,
        'callouts' => true,
        'calloutsHeadline' => true,
        'downloadCalloutExcerpt' => true,
        'disclaimerBlock' => true,
        'productCategory' => true,
        'make' => true,
        'model' => true,
        'replacesOEM' => true,
        'part' => true,
        'customSchematicIdentifierList' => true,
        'fax' => true,
        'footerLinks' => true,
        'relatedProducts' => true,
        'secondaryCalloutLink' => true,
        'calloutShowBottomBar' => true,
        'imageAdShowBottomBar' => true,
        'newProduct' => true,
        'calloutsImage' => true,
        'calloutsExcerpt' => true,
        'calloutsPrimaryButton' => true,
        'calloutsSecondaryButton' => true,
        'featuredParts' => true,
        'introContent' => true,
        'introContentHeadline' => true,
        'introContentExcerpt' => true,
        'introContentButton' => true,
        'heroImage' => true,
        'categoryIcon' => true,
        'featuredCategory' => true,
        'relatedSchematics' => true,
        'productDescription' => true,
        'imageGallery' => true,
        'relatedResources' => true,
        'internalUseCompatibleModels' => true,
        'orderNotes' => true,
        'businessName' => true,
        'contactPhone' => true,
        'defaultQuantity' => true,
        'jdfAccountNumber' => true,
        'relatedProductsHeadline' => true,
        'schematicOrder' => true,
        'featuredMakes' => true,
        'makeDisclaimer' => true,
        'howDidYouHearAboutKooimaCompany' => true,
        'subscribeToNewsletter' => true,
        'modelDisclaimer' => true,
        'featureFlag' => true,
        'rating' => true,
        'reviewTitle' => true,
        'lastNameInitial' => true,
        'productSku' => true,
        'updateProduct' => true,
        'phone' => true,
        'salesDocType' => true,
        'salespadCustomerNumber' => true,
        'responseTranscript' => true,
        'requestTranscript' => true,
        'salesDocNum' => true,
        'heroHeading' => true,
        'announcementHeading' => true,
        'homepageContent' => true,
        'announcementColorTheme' => true,
        'showAnnouncement' => true,
        'announcementSummary' => true,
        'partSelectorLink' => true,
        'partSelectorHeading' => true,
        'announcementLink' => true,
        'headingLink' => true,
        'heading' => true,
        'products' => true,
        'summary' => true,
        'primaryButton' => true,
        'secondaryButton' => true,
        'ctaLink' => true,
    ];

    /**
     * @var string|null Value for field with the handle “localPhoneNumber”.
     */
    public $localPhoneNumber;

    /**
     * @var string|null Value for field with the handle “headerHTML”.
     */
    public $headerHTML;

    /**
     * @var string|null Value for field with the handle “footerHTML”.
     */
    public $footerHTML;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “featuredImage”.
     */
    public $featuredImage;

    /**
     * @var string|null Value for field with the handle “excerpt”.
     */
    public $excerpt;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “entryContent”.
     */
    public $entryContent;

    /**
     * @var string|null Value for field with the handle “accordionHeadline”.
     */
    public $accordionHeadline;

    /**
     * @var string Value for field with the handle “accordionContent”.
     */
    public $accordionContent;

    /**
     * @var string Value for field with the handle “blockquoteContent”.
     */
    public $blockquoteContent;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “blockquotePosition”.
     */
    public $blockquotePosition;

    /**
     * @var string|null Value for field with the handle “buttonLabel”.
     */
    public $buttonLabel;

    /**
     * @var mixed Value for field with the handle “buttonLink”.
     */
    public $buttonLink;

    /**
     * @var string|null Value for field with the handle “customCode”.
     */
    public $customCode;

    /**
     * @var string|null Value for field with the handle “embeddedVideoSource”.
     */
    public $embeddedVideoSource;

    /**
     * @var string Value for field with the handle “contentBlock”.
     */
    public $contentBlock;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “image”.
     */
    public $image;

    /**
     * @var string|null Value for field with the handle “imageCaption”.
     */
    public $imageCaption;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “imagePosition”.
     */
    public $imagePosition;

    /**
     * @var string|null Value for field with the handle “imageGalleryTitle”.
     */
    public $imageGalleryTitle;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “imageGalleryImages”.
     */
    public $imageGalleryImages;

    /**
     * @var string|null Value for field with the handle “accessibilityText”.
     */
    public $accessibilityText;

    /**
     * @var string Value for field with the handle “pullquoteContent”.
     */
    public $pullquoteContent;

    /**
     * @var string|null Value for field with the handle “pullquoteAttribution”.
     */
    public $pullquoteAttribution;

    /**
     * @var mixed Value for field with the handle “form”.
     */
    public $form;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “buttonStyle”.
     */
    public $buttonStyle;

    /**
     * @var string|null Value for field with the handle “caption”.
     */
    public $caption;

    /**
     * @var string Value for field with the handle “imageGalleryIntroContent”.
     */
    public $imageGalleryIntroContent;

    /**
     * @var string Value for field with the handle “formIntroContent”.
     */
    public $formIntroContent;

    /**
     * @var mixed Value for field with the handle “externalLink”.
     */
    public $externalLink;

    /**
     * @var mixed Value for field with the handle “metaSeoData”.
     */
    public $metaSeoData;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “primaryNavigation”.
     */
    public $primaryNavigation;

    /**
     * @var mixed Value for field with the handle “navLink”.
     */
    public $navLink;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “auxNavigation”.
     */
    public $auxNavigation;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “socialNetworks”.
     */
    public $socialNetworks;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “socialNetworkName”.
     */
    public $socialNetworkName;

    /**
     * @var mixed Value for field with the handle “socialNetworkLink”.
     */
    public $socialNetworkLink;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “pullquotePosition”.
     */
    public $pullquotePosition;

    /**
     * @var mixed Value for field with the handle “calloutLink”.
     */
    public $calloutLink;

    /**
     * @var string|null Value for field with the handle “calloutBlockHeadline”.
     */
    public $calloutBlockHeadline;

    /**
     * @var string|null Value for field with the handle “calloutBlockContent”.
     */
    public $calloutBlockContent;

    /**
     * @var mixed Value for field with the handle “calloutBlockLink”.
     */
    public $calloutBlockLink;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “embeddedVideoPosition”.
     */
    public $embeddedVideoPosition;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “newsCategory”.
     */
    public $newsCategory;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “blogCategory”.
     */
    public $blogCategory;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “calloutBlockImage”.
     */
    public $calloutBlockImage;

    /**
     * @var string|null Value for field with the handle “downloadCalloutTitle”.
     */
    public $downloadCalloutTitle;

    /**
     * @var mixed Value for field with the handle “downloadCalloutLink”.
     */
    public $downloadCalloutLink;

    /**
     * @var bool Value for field with the handle “showChildPages”.
     */
    public $showChildPages;

    /**
     * @var mixed Value for field with the handle “imageLink”.
     */
    public $imageLink;

    /**
     * @var string|null Value for field with the handle “positionTitle”.
     */
    public $positionTitle;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “employmentType”.
     */
    public $employmentType;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “baseSalary”.
     */
    public $baseSalary;

    /**
     * @var int|float|null Value for field with the handle “baseSalaryMinimumValue”.
     */
    public $baseSalaryMinimumValue;

    /**
     * @var int|float|null Value for field with the handle “baseSalaryMaximumValue”.
     */
    public $baseSalaryMaximumValue;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “baseSalaryUnit”.
     */
    public $baseSalaryUnit;

    /**
     * @var string Value for field with the handle “educationRequirements”.
     */
    public $educationRequirements;

    /**
     * @var string Value for field with the handle “experienceRequirements”.
     */
    public $experienceRequirements;

    /**
     * @var string|null Value for field with the handle “industry”.
     */
    public $industry;

    /**
     * @var string Value for field with the handle “qualifications”.
     */
    public $qualifications;

    /**
     * @var string Value for field with the handle “responsibilities”.
     */
    public $responsibilities;

    /**
     * @var string Value for field with the handle “skills”.
     */
    public $skills;

    /**
     * @var string|null Value for field with the handle “workHours”.
     */
    public $workHours;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “sidebarWidgets”.
     */
    public $sidebarWidgets;

    /**
     * @var string|null Value for field with the handle “calloutHeadline”.
     */
    public $calloutHeadline;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “calloutImage”.
     */
    public $calloutImage;

    /**
     * @var string|null Value for field with the handle “calloutExcerpt”.
     */
    public $calloutExcerpt;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “imageAdImage”.
     */
    public $imageAdImage;

    /**
     * @var mixed Value for field with the handle “imageAdLink”.
     */
    public $imageAdLink;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “sidebarWidgetsSelection”.
     */
    public $sidebarWidgetsSelection;

    /**
     * @var bool Value for field with the handle “showSubnavigation”.
     */
    public $showSubnavigation;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock>|\verbb\supertable\elements\db\SuperTableBlockQuery|\craft\elements\ElementCollection<\verbb\supertable\elements\SuperTableBlockElement> Value for field with the handle “callouts”.
     */
    public $callouts;

    /**
     * @var string|null Value for field with the handle “calloutsHeadline”.
     */
    public $calloutsHeadline;

    /**
     * @var string|null Value for field with the handle “downloadCalloutExcerpt”.
     */
    public $downloadCalloutExcerpt;

    /**
     * @var string|null Value for field with the handle “disclaimerBlock”.
     */
    public $disclaimerBlock;

    /**
     * @var \craft\elements\db\CategoryQuery|\craft\elements\ElementCollection<\craft\elements\Category> Value for field with the handle “productCategory”.
     */
    public $productCategory;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “make”.
     */
    public $make;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “model”.
     */
    public $model;

    /**
     * @var string|null Value for field with the handle “replacesOEM”.
     */
    public $replacesOEM;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “part”.
     */
    public $part;

    /**
     * @var array|null Value for field with the handle “customSchematicIdentifierList”.
     */
    public $customSchematicIdentifierList;

    /**
     * @var string|null Value for field with the handle “fax”.
     */
    public $fax;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “footerLinks”.
     */
    public $footerLinks;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “relatedProducts”.
     */
    public $relatedProducts;

    /**
     * @var mixed Value for field with the handle “secondaryCalloutLink”.
     */
    public $secondaryCalloutLink;

    /**
     * @var bool Value for field with the handle “calloutShowBottomBar”.
     */
    public $calloutShowBottomBar;

    /**
     * @var bool Value for field with the handle “imageAdShowBottomBar”.
     */
    public $imageAdShowBottomBar;

    /**
     * @var bool Value for field with the handle “newProduct”.
     */
    public $newProduct;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “calloutsImage”.
     */
    public $calloutsImage;

    /**
     * @var string|null Value for field with the handle “calloutsExcerpt”.
     */
    public $calloutsExcerpt;

    /**
     * @var mixed Value for field with the handle “calloutsPrimaryButton”.
     */
    public $calloutsPrimaryButton;

    /**
     * @var mixed Value for field with the handle “calloutsSecondaryButton”.
     */
    public $calloutsSecondaryButton;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “featuredParts”.
     */
    public $featuredParts;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “introContent”.
     */
    public $introContent;

    /**
     * @var string|null Value for field with the handle “introContentHeadline”.
     */
    public $introContentHeadline;

    /**
     * @var string|null Value for field with the handle “introContentExcerpt”.
     */
    public $introContentExcerpt;

    /**
     * @var mixed Value for field with the handle “introContentButton”.
     */
    public $introContentButton;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “heroImage”.
     */
    public $heroImage;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “categoryIcon”.
     */
    public $categoryIcon;

    /**
     * @var bool Value for field with the handle “featuredCategory”.
     */
    public $featuredCategory;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “relatedSchematics”.
     */
    public $relatedSchematics;

    /**
     * @var string Value for field with the handle “productDescription”.
     */
    public $productDescription;

    /**
     * @var \craft\elements\db\AssetQuery|\craft\elements\ElementCollection<\craft\elements\Asset> Value for field with the handle “imageGallery”.
     */
    public $imageGallery;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “relatedResources”.
     */
    public $relatedResources;

    /**
     * @var string|null Value for field with the handle “internalUseCompatibleModels”.
     */
    public $internalUseCompatibleModels;

    /**
     * @var string|null Value for field with the handle “orderNotes”.
     */
    public $orderNotes;

    /**
     * @var string|null Value for field with the handle “businessName”.
     */
    public $businessName;

    /**
     * @var string|null Value for field with the handle “contactPhone”.
     */
    public $contactPhone;

    /**
     * @var int|float|null Value for field with the handle “defaultQuantity”.
     */
    public $defaultQuantity;

    /**
     * @var string|null Value for field with the handle “jdfAccountNumber”.
     */
    public $jdfAccountNumber;

    /**
     * @var string|null Value for field with the handle “relatedProductsHeadline”.
     */
    public $relatedProductsHeadline;

    /**
     * @var int|float|null Value for field with the handle “schematicOrder”.
     */
    public $schematicOrder;

    /**
     * @var \craft\elements\db\EntryQuery|\craft\elements\ElementCollection<\craft\elements\Entry> Value for field with the handle “featuredMakes”.
     */
    public $featuredMakes;

    /**
     * @var string|null Value for field with the handle “makeDisclaimer”.
     */
    public $makeDisclaimer;

    /**
     * @var string|null Value for field with the handle “howDidYouHearAboutKooimaCompany”.
     */
    public $howDidYouHearAboutKooimaCompany;

    /**
     * @var bool Value for field with the handle “subscribeToNewsletter”.
     */
    public $subscribeToNewsletter;

    /**
     * @var string|null Value for field with the handle “modelDisclaimer”.
     */
    public $modelDisclaimer;

    /**
     * @var bool Value for field with the handle “featureFlag”.
     */
    public $featureFlag;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “rating”.
     */
    public $rating;

    /**
     * @var string|null Value for field with the handle “reviewTitle”.
     */
    public $reviewTitle;

    /**
     * @var string|null Value for field with the handle “lastNameInitial”.
     */
    public $lastNameInitial;

    /**
     * @var string|null Value for field with the handle “productSku”.
     */
    public $productSku;

    /**
     * @var mixed Value for field with the handle “updateProduct”.
     */
    public $updateProduct;

    /**
     * @var string|null Value for field with the handle “phone”.
     */
    public $phone;

    /**
     * @var string|null Value for field with the handle “salesDocType”.
     */
    public $salesDocType;

    /**
     * @var string|null Value for field with the handle “salespadCustomerNumber”.
     */
    public $salespadCustomerNumber;

    /**
     * @var string Value for field with the handle “responseTranscript”.
     */
    public $responseTranscript;

    /**
     * @var string Value for field with the handle “requestTranscript”.
     */
    public $requestTranscript;

    /**
     * @var string|null Value for field with the handle “salesDocNum”.
     */
    public $salesDocNum;

    /**
     * @var string|null Value for field with the handle “heroHeading”.
     */
    public $heroHeading;

    /**
     * @var string|null Value for field with the handle “announcementHeading”.
     */
    public $announcementHeading;

    /**
     * @var \craft\elements\db\MatrixBlockQuery|\craft\elements\ElementCollection<\craft\elements\MatrixBlock> Value for field with the handle “homepageContent”.
     */
    public $homepageContent;

    /**
     * @var \craft\fields\data\SingleOptionFieldData Value for field with the handle “announcementColorTheme”.
     */
    public $announcementColorTheme;

    /**
     * @var bool Value for field with the handle “showAnnouncement”.
     */
    public $showAnnouncement;

    /**
     * @var string Value for field with the handle “announcementSummary”.
     */
    public $announcementSummary;

    /**
     * @var mixed Value for field with the handle “partSelectorLink”.
     */
    public $partSelectorLink;

    /**
     * @var string|null Value for field with the handle “partSelectorHeading”.
     */
    public $partSelectorHeading;

    /**
     * @var mixed Value for field with the handle “announcementLink”.
     */
    public $announcementLink;

    /**
     * @var mixed Value for field with the handle “headingLink”.
     */
    public $headingLink;

    /**
     * @var string|null Value for field with the handle “heading”.
     */
    public $heading;

    /**
     * @var \craft\elements\db\ElementQueryInterface|\craft\elements\ElementCollection<\craft\base\ElementInterface> Value for field with the handle “products”.
     */
    public $products;

    /**
     * @var string Value for field with the handle “summary”.
     */
    public $summary;

    /**
     * @var mixed Value for field with the handle “primaryButton”.
     */
    public $primaryButton;

    /**
     * @var mixed Value for field with the handle “secondaryButton”.
     */
    public $secondaryButton;

    /**
     * @var mixed Value for field with the handle “ctaLink”.
     */
    public $ctaLink;

    /**
     * @var array Additional custom field values we don’t know about yet.
     */
    private array $_customFieldValues = [];

    /**
     * @inheritdoc
     */
    public function __call($name, $params)
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name]) && count($params) === 1) {
            $this->$name = $params[0];
            return $this->owner;
        }
        return parent::__call($name, $params);
    }

    /**
     * @inheritdoc
     */
    public function hasMethod($name): bool
    {
        if ($this->hasMethods && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::hasMethod($name);
    }

    /**
     * @inheritdoc
     */
    public function __isset($name): bool
    {
        if (isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::__isset($name);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if (isset(self::$fieldHandles[$name])) {
            return $this->_customFieldValues[$name] ?? null;
        }
        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (isset(self::$fieldHandles[$name])) {
            $this->_customFieldValues[$name] = $value;
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true): bool
    {
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true): bool
    {
        if (!$this->canSetProperties) {
            return false;
        }
        if ($checkVars && isset(self::$fieldHandles[$name])) {
            return true;
        }
        return parent::canSetProperty($name, $checkVars);
    }
}

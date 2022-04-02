<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use K2D\Gallery\Models\GalleryModel;
use K2D\News\Models\NewModel;

class NewPresenter extends BasePresenter
{

    /** @inject */
    public GalleryModel $galleryModel;

    /** @inject */
    public NewModel $newModel;

    public function renderDefault(): void
    {
        $this->template->news = $this->newModel->getPublicNews('cs')->limit(3);
    }

    public function renderShow(string $slug): void
    {
        $new = $this->newModel->getNew($slug, 'cs');
        $this->template->new = $new;

        if (isset($new->gallery_id))
            $this->template->gallery = $this->galleryModel->getGallery($new->gallery_id);
    }

}

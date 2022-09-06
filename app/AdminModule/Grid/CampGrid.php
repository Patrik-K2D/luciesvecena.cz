<?php declare(strict_types=1);

namespace App\AdminModule\Grid;

use App\Model\CampModel;
use K2D\Core\AdminModule\Grid\BaseV2Grid;
use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;

class CampGrid extends BaseV2Grid
{

	/** @inject CampModel */
	public CampModel $campModel;

    public function __construct(CampModel $campModel)
    {
        parent::__construct();
        $this->campModel = $campModel;
    }

	protected function build(): void
	{
		$this->model = $this->campModel;

		parent::build();

		$this->setDefaultOrderBy('created', true);
		$this->setFilterFactory([$this, 'gridFilterFactory']);

		$this->addColumn('name', 'Název');
		$this->addColumn('date_from', 'Začátek');
		$this->addColumn('date_to', 'Konec');
		$this->addColumn('participant', 'Kapacita');
		$this->addColumn('created', 'Vytvořeno')->setSortable();
		$this->addColumn('public', 'Veřejný');

        $this->addRowAction('show', 'Zobrazit přihlášené', static function () {});
		$this->addRowAction('edit', 'Upravit', static function () {});
		$this->addRowAction('delete', 'Smazat', static function (ActiveRow $record) {
			$record->delete();
		})
			->setProtected(false)
			->setConfirmation('Opravdu chcete tento kemp smazat?');
	}

	public function gridFilterFactory(Container $c): void
	{
		$c->addText('name');
		$c->addSelect('public')
			->setPrompt('---')
			->setItems([0 => 'Ne', 1 => 'Ano']);
	}
}

<?php declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Grid\CampGrid;
use App\AdminModule\Grid\CampGridFactory;
use App\Model\CampModel;
use App\Model\ParticipantModel;
use K2D\Core\AdminModule\Presenter\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Localization\SimpleTranslator;
use function date_create_from_format;

/**
 * @property-read ActiveRow|null $camp
 */
class CampPresenter extends BasePresenter
{

    /** @inject */
    public CampModel $campModel;

    /** @inject ParticipantModel */
    public ParticipantModel $participantModel;

    /** @inject */
    public CampGridFactory $campGridFactory;

    public function startup(): void
    {
        parent::startup();
    }

    public function renderEdit(?int $id = null): void
    {
        $this->template->camp = null;
        $this->template->userId = $this->adminFirewall->getUser()->id;

        if ($id !== null && $this->camp !== null) {
            $camp = $this->camp->toArray();

            $date = $camp['date_from'];
            $camp['date_from'] = $date->format('j.n.Y');

            $date = $camp['date_to'];
            $camp['date_to'] = $date->format('j.n.Y');

            $form = $this['editForm'];
            $form->setDefaults($camp);

            $this->template->camp = $this->camp;
        }
    }

    public function renderShow($camp_id): void
    {
        $this->template->camp = $this->campModel->getCamp((int)$camp_id);
    }

    public function createComponentEditForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Název kempu')
            ->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 255)
            ->setRequired('Musíte uvést název kempu');

        $form->addText('date_from', 'Začátek kempu')
            ->setDefaultValue((new DateTime())->format('j.n.Y'))
            ->setRequired('Musíte uvést počáteční datum kempu');

        $form->addText('date_to', 'Konec kempu')
            ->setDefaultValue((new DateTime())->format('j.n.Y'))
            ->setRequired('Musíte uvést konečné datum kempu');

        $form->addText('capacity', 'Maximální kapacita')
            ->addRule(Form::MAX_LENGTH, 'Maximálné délka je %s znaků', 255)
            ->setRequired('Musíte uvést název kempu');

        $form->addCheckbox('public', 'Zveřejnit')
            ->setDefaultValue(true);

        $form->addTextArea('description', 'Popis kempu', 100, 35)
            ->setHtmlAttribute('class', 'form-wysiwyg');

        $form->addSubmit('save', 'Uložit');

        $form->onSubmit[] = function (Form $form): void {
            $values = $form->getValues(true);
            $values['date_from'] = date_create_from_format('j.n.Y', $values['date_from'])->setTime(0, 0, 0);
            $values['date_to'] = date_create_from_format('j.n.Y', $values['date_to'])->setTime(0, 0, 0);

            $camp = $this->camp;

            if ($camp === null) {
                $camp = $this->campModel->insert($values);
                $this->flashMessage('Kemp vytvořen');
            } else {
                $camp->update($values);
                $this->flashMessage('Kemp upraven');
            }

            $this->redirect('this', ['id' => $camp->id]);
        };

        return $form;
    }

    protected function createComponentCampGrid(): CampGrid
    {
        return $this->campGridFactory->create();
    }

    protected function getCamp(): ?ActiveRow
    {
        return $this->campModel->get($this->getParameter('id'));
    }


    // render startlist table
    public function createComponentAdminParticipantsListGrid(): Multiplier
    {
        return new Multiplier(function ($camp_id) {

            $grid = new DataGrid();

            $camp_id = (int)$camp_id;

            $grid->setDefaultSort('id');

            $grid->setDataSource($this->participantModel->getParticipantsByCamp($camp_id));

            $grid->setItemsPerPageList([25, 50, 100, 250], true);

            $grid->addColumnText('id', 'ID')
                ->setSortable();

            $grid->addColumnText('name', 'Jméno')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nové jméno: %s', $id, $value));
                    $participant = $this->participantModel->getParticipant($id);
                    $participant->update(['name' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('surname', 'Příjmení')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nové příjmení: %s', $id, $value));
                    $participant = $this->participantModel->getParticipant($id);
                    $participant->update(['surname' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('year_of_birth', 'Rok narození')
                ->setAlign('center')
                ->setFitContent()
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nový rok narození: %s', $id, $value));
                    $participant = $this->participantModel->getParticipant($id);
                    $participant->update(['year_of_birth' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('team', 'Oddíl')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Změna u řádku ID: %s, Nový název města/oddílu: %s', $id, $value));
                    $competitior = $this->participantModel->getParticipant($id);
                    $competitior->update(['team' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addColumnText('sex', 'Pohlaví')
                ->setAlign('center')
                ->setFitContent();

            $grid->addColumnText('email', 'Email')
                ->setSortable()
                ->setEditableCallback(function ($id, $value): void {
                    $this->flashMessage(sprintf('Id: %s, new value: %s', $id, $value));
                    $competitior = $this->participantModel->getParticipant($id);
                    $competitior->update(['email' => $value]);
                    $this->redrawControl('flashes');
                });

            $grid->addExportCsv('Csv export', 'ucastnici-kempu.csv')
                ->setTitle('Export CSV');

            $translator = new SimpleTranslator([
                'ublaboo_datagrid.no_item_found_reset' => 'Žádné záznamy nenalezeny. Filtr můžete vynulovat',
                'ublaboo_datagrid.no_item_found' => 'Žádné záznamy nenalezeny.',
                'ublaboo_datagrid.here' => 'zde',
                'ublaboo_datagrid.items' => 'Záznamy',
                'ublaboo_datagrid.all' => 'všechny',
                'ublaboo_datagrid.from' => 'z',
                'ublaboo_datagrid.reset_filter' => 'Resetovat filtr',
                'ublaboo_datagrid.group_actions' => 'Hromadné akce',
                'ublaboo_datagrid.show_all_columns' => 'Zobrazit všechny sloupce',
                'ublaboo_datagrid.hide_column' => 'Skrýt sloupec',
                'ublaboo_datagrid.action' => 'Akce',
                'ublaboo_datagrid.previous' => 'Předchozí',
                'ublaboo_datagrid.next' => 'Další',
                'ublaboo_datagrid.choose' => 'Vyberte',
                'ublaboo_datagrid.execute' => 'Provést',
            ]);

            $grid->setTranslator($translator);

            return $grid;
        });
    }
}

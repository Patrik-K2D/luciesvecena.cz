<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use App\Model\CampModel;
use App\Model\ParticipantModel;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Mail\SmtpException;
use Nette\Mail\SmtpMailer;
use Nette\Neon\Neon;
use Nette\Utils\Paginator;

class CampPresenter extends BasePresenter
{

    /** @inject */
    public CampModel $campModel;

    /** @inject ParticipantModel */
    public ParticipantModel $participantModel;

    public function renderDefault(int $page = 1): void
    {
        $campsCount = $this->campModel->getPublicCamps()->count();

        $paginator = new Paginator;
        $paginator->setPage($page); // číslo aktuální stránky
        $paginator->setItemsPerPage(1); // počet položek na stránce
        $paginator->setItemCount($campsCount); // celkový počet položek, je-li znám

        $camps = $this->repository->getPublicCamps()->limit($paginator->getLength(), $paginator->getOffset());

        $this->template->camps = $camps;
        $this->template->paginator = $paginator;
    }

    public function renderShow(int $id): void
    {
        $this->template->camp = $this->campModel->getCamp($id);
    }


    public function createComponentSignUpForm($camp_id): Form
    {
        $form = new Form();

        $form->addText('camp_id');

        $form->addText('name', 'Jméno')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 60)
            ->setRequired('Musíte zadat Vaše jméno.');

        $form->addText('surname', 'Příjmení')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 60)
            ->setRequired('Musíte zadat Vaše příjmení.');

        $form->addInteger('year_of_birth', 'Rok narození')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka jsou %s znaky', 4)
            ->setRequired('Musíte zadat Váš rok narození.');

        $sex = [
            'M' => 'Muž',
            'Ž' => 'Žena',
        ];
        $form->addRadioList('sex', 'Pohlaví', $sex)
            ->setRequired();

        $form->addText('team', 'Oddíl (volitelné)')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 255);

        $form->addEmail('email', 'Email')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 255)
            ->setRequired('Musíte zadat Váš email.');

        $form->addTextArea('note', 'Poznámka (volitelné)')
            ->addRule($form::MAX_LENGTH, 'Zpráva je příliš dlouhá', 2000);

        $form->addSubmit('submit', 'Odeslat přihlášku');

        $form->onSubmit[] = function (Form $form) {
            try {
                $values = $form->getValues();

                if ($this->participantModel->insert($values)->id) {
                    $camp = $this->campModel->getCamp((int)$values['camp_id']);
                    $camp->update([
                        'participants' => $camp->participants + 1
                    ]);

                    $date_from = date_format($camp->date_from,"d. m.");
                    $date_to = date_format($camp->date_to,"d. m.");

                    // send mail
                    $latte = new Engine;
                    $params = [
                        'camp_name' => $camp->name,
                        'date_from' => $date_from,
                        'date_to' => $date_to,
                        'name' => $values['name'],
                        'surname' => $values['surname'],
                        'sex' => $values['sex'],
                        'year_of_birth' => $values['year_of_birth'],
                        'team' => $values['team'],
                        'note' => $values['note']
                    ];

                    $mail = new Message();

                    $mail->setFrom('info@luciesvecena.cz', 'Lucie Svěcená');
                    $mail->addTo($values['email']);
                    $mail->setHtmlBody(
                        $latte->renderToString(__DIR__ . '/../../Email/camp.latte', $params),
                        __DIR__ . '/../../assets/img/email');
                    $parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));

                    $mailer = new SmtpMailer([
                        'host' => $parameters['mail']['host'],
                        'username' => $parameters['mail']['username'],
                        'password' => $parameters['mail']['password'],
                        'secure' => $parameters['mail']['secure'],
                    ]);
                    $mailer->send($mail);

                    $this->flashMessage('Přihláška byla úspěšně odeslána!', 'success');
                } else {
                    $this->flashMessage('Přihlášku se nepodařilo odeslat!', 'danger');
                }

                if ($this->isAjax()) {
                    $this->redrawControl('signUpFlashes');
                    $this->redrawControl('signUpForm');
                    $form->setValues([], TRUE);
                } else {
                    $this->redirect('this');
                }

            } catch (SmtpException $e) {
                $this->flashMessage('Vaši zprávu se nepodařilo odeslat. Kontaktujte prosím správce webu na info@filipurban.cz', 'danger');
            }
        };

        return $form;
    }
}

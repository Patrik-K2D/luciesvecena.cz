<?php declare(strict_types = 1);

namespace App\FrontModule\Presenters;

use Contributte\PdfResponse\PdfResponse;
use K2D\Gallery\Models\GalleryModel;
use K2D\News\Models\NewModel;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpException;
use Nette\Mail\SmtpMailer;
use Nette\Neon\Neon;
use Nette\Utils\Strings;

class HomepagePresenter extends BasePresenter
{

    /** @inject */
    public GalleryModel $galleryModel;

    /** @inject */
    public NewModel $newModel;

	public function renderDefault(): void
	{
        $this->template->news = $this->newModel->getPublicNews('cs')->limit(2);
        $this->template->gallery = $this->galleryModel->getGallery(1);
	}

    public function renderTemplate(): void
    {

    }

    public function createComponentContactForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Jméno a příjmení')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 100)
            ->setRequired('Musíte zadat Vaše jméno a příjmení.');

        $form->addEmail('email', 'Email')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 150)
            ->setRequired('Musíte zadat Váš email.');

        $form->addTextArea('message', 'Zpráva')
            ->addRule($form::MAX_LENGTH, 'Zpráva je příliš dlouhá', 5000)
            ->setRequired('Obsah zprávy nemůže zůstat prázdný.');
//
//        $form->addInvisibleReCaptcha('recaptcha')
//            ->setMessage('Jste opravdu člověk?');

        $form->addSubmit('submit', 'Odeslat zprávu');

        $form->onSubmit[] = function (Form $form) {
            try {
                $values = $form->getValues();

                $mail = new Message();

                $vars = $this->configuration->getAllVars();
                if (isset($vars['email']))
                    $ownersEmail = $vars['email'];
                else
                    $ownersEmail = 'info@filipurban.cz';

                $mail->setFrom($values['email'], $values['name'])
                    ->addTo($ownersEmail)
                    ->setSubject('Zpráva z kontaktního formuláře (luciesvecena.cz)')
                    ->setBody($values['message']);

                $parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));

                $mailer = new SmtpMailer([
                    'host' => $parameters['mail']['host'],
                    'username' => $parameters['mail']['username'],
                    'password' => $parameters['mail']['password'],
                    'secure' => $parameters['mail']['secure'],
                ]);

                $mailer->send($mail);

                $this->flashMessage('Email byl úspěšně odeslán!', 'success');

                if ($this->isAjax()) {
                    $this->redrawControl('contactFlashes');
                    $this->redrawControl('contactForm');
                    $form->setValues([], TRUE);
                } else {
                    $this->redirect('this#kontakt');
                }

            } catch (SmtpException $e) {
                $this->flashMessage('Vaši zprávu se nepodařilo odeslat. Kontaktujte prosím správce webu na info@filipurban.cz', 'danger');
            }
        };

        return $form;
    }


    public function createComponentVoucherForm(): Form
    {
        $form = new Form();

        $form->addText('name', 'Jméno a příjmení')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 100)
            ->setRequired('Musíte zadat jméno a příjmení obdarovaného.');

        $form->addInteger('lessons', 'Počet lekcí')
            ->setDefaultValue(1)
            ->addRule(Form::MIN, 'Minimálně %s lekce', 1)
            ->addRule(Form::MAX, 'Maximálně %s lekcí', 10)
            ->setRequired('Musíte zadat počet lekcí.');

        $form->addEmail('email', 'Email')
            ->addRule(Form::MAX_LENGTH, 'Maximální délka je %s znaků', 150)
            ->setRequired('Musíte zadat Váš email.');
//
//        $form->addInvisibleReCaptcha('recaptcha')
//            ->setMessage('Jste opravdu člověk?');

        $form->addSubmit('submit', 'Odeslat objednávku');

        $form->onSubmit[] = function (Form $form) {
            try {
                $values = $form->getValues();

                $variableSymbol = substr((string)strtotime("now"),0,5) . rand(100, 999);
                $validity = date('d. m. Y', strtotime("+6 months"));
                $pdfName = Strings::webalize($values['name']) . $variableSymbol;

                // create pdf
                $template = $this->createTemplate();
                $template->setFile(__DIR__ . "/../../Voucher/template.latte");
                $template->name = $values['name'];
                $template->lessons = $values['lessons'];
                $template->variableSymbol = $variableSymbol;
                $template->validity = $validity;

                // save pdf
                $pdf = new PdfResponse($template);
                $pdf->documentTitle = $pdfName;
                $pdf->pageFormat = "A5-L"; // wide format
                $savedFile = $pdf->save(__DIR__ . "/../../../www/vouchery/");

                // send mail to admin
                $mail = new Message();
                $vars = $this->configuration->getAllVars();
                if (isset($vars['email']))
                    $ownersEmail = $vars['email'];
                else
                    $ownersEmail = 'info@filipurban.cz';

                $mail->setFrom('info@luciesvecena.cz', 'Lucie Svěcená')
                    ->addTo($ownersEmail)
                    ->setSubject('LucieSvěcená.cz - objednávka dárkového poukazu')
                    ->setHtmlBody(
                        '<h3>Právě byl zakoupen dárkový poukaz!</h3>
                        <p><small>Informace o objednávce:</small><br><br>
                        Jméno: <b>'. $values['name'] . '</b><br>
                        Počet lekcí: <b>'. $values['lessons'] .' (cena '. $values['lessons'] * 600 .',- Kč)</b><br>
                        Kód poukazu: <b>'. $variableSymbol .'</b><br>
                        Platnost: <b>'. $validity .'</p>
                    ')
                    ->addAttachment($savedFile);

                $parameters = Neon::decode(file_get_contents(__DIR__ . "/../../config/server/local.neon"));

                $mailer = new SmtpMailer([
                    'host' => $parameters['mail']['host'],
                    'username' => $parameters['mail']['username'],
                    'password' => $parameters['mail']['password'],
                    'secure' => $parameters['mail']['secure'],
                ]);
                $mailer->send($mail);



                // send mail to customer
                $latte = new Engine;
                $params = [
                    'name' => $values['name'],
                    'lessons' => $values['lessons'],
                    'price' => $values['lessons'] * 600,
                    'variableSymbol' => $variableSymbol,
                    'validity' => $validity,
                    'message' => 'Darkovy poukaz (v.s. ' . $variableSymbol . ')',
                    'pdfName' => $pdfName
                ];

                $mail2 = new Message();

                $mail2->setFrom('info@luciesvecena.cz', 'Lucie Svěcená')
                    ->addTo($values['email'])
                    ->setSubject('Dárkový poukaz na lekce plavání (luciesvecena.cz)')
                    ->setHtmlBody(
                    $latte->renderToString(__DIR__ . '/../../Email/voucher.latte', $params),
                    __DIR__ . '/../../assets/img/email')
                    ->addAttachment($savedFile);

                $mailer2 = new SmtpMailer([
                    'host' => $parameters['mail']['host'],
                    'username' => $parameters['mail']['username'],
                    'password' => $parameters['mail']['password'],
                    'secure' => $parameters['mail']['secure'],
                ]);
                $mailer2->send($mail2);

                $this->flashMessage('Objednávka proběhla v pořádku! Dárkový poukaz byl zaslán na zadanou adresu.', 'success');

                if ($this->isAjax()) {
                    $this->redrawControl('voucherFlashes');
                    $this->redrawControl('voucherForm');
                    $form->setValues([], TRUE);
                } else {
                    $this->redirect('this#darkovy-poukaz');
                }

            } catch (SmtpException $e) {
                $this->flashMessage('Objednávku se nepodařilo dokončit. Kontaktujte prosím správce webu na info@filipurban.cz', 'danger');
            }
        };

        return $form;
    }

}

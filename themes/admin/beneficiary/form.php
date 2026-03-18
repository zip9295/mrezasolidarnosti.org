<?php

use Skeletor\Form\InputGroup\InputGroup;
use Skeletor\Form\InputGroup\InputGroupWidth;
use Skeletor\Form\InputTypes\Input\Text;
use Skeletor\Form\InputTypes\Select\Collection\OptionCollection;
use Skeletor\Form\InputTypes\Select\Option;
use Skeletor\Form\InputTypes\Select\Select;
use Skeletor\Form\Renderer\TabbedFormRenderer;
use Skeletor\Form\Tab\Tab;
use Skeletor\Form\TabbedForm;

$form = new TabbedForm($data['formAction'], $data['dataAction'], $this->formTokenArray());

$action = $data['dataAction'] === 'create' ? 'Kreiraj' : 'Izmeni';

$statuses = \Solidarity\Beneficiary\Entity\Beneficiary::getHrStatuses();
$statusCollection = (new OptionCollection(new Option('1', 'New')))->fromArray($statuses, $data['model']?->status);
$statusSelect = (new Select('status', $statusCollection, 'Status'));
$name = (new Text('name', $data['model']?->name, 'Name'))->required("Ime je obavezno");
$accountNumber = (new Text('accountNumber', $data['model']?->accountNumber, 'Broj računa'));
$comment = (new \Skeletor\Form\InputTypes\TextArea\TextArea('comment', $data['model']?->comment, 'Komentar'));

$delegateMspSelect = (new \Skeletor\Form\InputTypes\AjaxInputSearch\AjaxInputSearch(
    'createdBy',
    '/delegate/tableHandler/',
    'name',
    'id',
    'Delegat',
    $data['model']?->createdBy?->id ?? null,
    $data['model']?->createdBy?->name,
    'Traži delegate...',
    ['p.id' => 1]
))->required('Morate izabrati delegata');

$schoolSelect = (new \Skeletor\Form\InputTypes\AjaxInputSearch\AjaxInputSearch(
    'school',
    '/school/tableHandler/',
    'name',
    'id',
    'Škola',
    $data['model']?->school?->id ?? null,
    $data['model']?->school?->name,
    'Trazi škole...',
));

$mspTab = (new Tab('Osnovne Info'))
    ->addInputGroup((new InputGroup())
        ->addInput($name))
    ->addInputGroup((new InputGroup())
        ->addInput($accountNumber))
    ->addInputGroup((new InputGroup())
        ->addInput($statusSelect))
    ->addInputGroup((new InputGroup(width: InputGroupWidth::HALF_WIDTH))
        ->addInput($schoolSelect)
        ->addInput($delegateMspSelect)
        ->addInput($comment)
    );

$form->addTab($mspTab);

$formRenderer = new TabbedFormRenderer($form, $data['formTitle']);

// Prepare existing registered periods for JS
$existingRegisteredPeriods = [];
if ($data['model']?->registeredPeriods) {
    foreach ($data['model']->registeredPeriods as $rp) {
        $existingRegisteredPeriods[] = [
            'period' => $rp->period->getId(),
            'project' => $rp->project->getId(),
            'amount' => $rp->amount,
        ];
    }
}

// Inject registered periods section inside the form tab
$registeredPeriodsHtml = '
<div class="registered-periods-section" style="padding: 10px 0;"
     id="registered-periods-container"
     data-periods="' . htmlspecialchars(json_encode($data['periods'])) . '"
     data-projects="' . htmlspecialchars(json_encode($data['projects'])) . '"
     data-existing="' . htmlspecialchars(json_encode($existingRegisteredPeriods)) . '">
    <h4>Registered Periods</h4>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 8px;">Project</th>
                <th style="text-align: left; padding: 8px;">Period</th>
                <th style="text-align: left; padding: 8px;">Amount</th>
                <th style="padding: 8px; width: 60px;"></th>
            </tr>
        </thead>
        <tbody id="registered-periods-body">
        </tbody>
    </table>
    <button type="button" id="add-period-row" style="margin-top: 10px;" class="btn btn-sm btn-primary">+ Add Period</button>
</div>';

$formRenderer->setAdditionalTabContent($mspTab, $registeredPeriodsHtml);
?>
<?= $formRenderer->render() ?>

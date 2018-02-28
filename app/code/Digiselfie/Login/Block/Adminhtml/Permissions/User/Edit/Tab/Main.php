<?php
namespace Digiselfie\Login\Block\Adminhtml\Permissions\User\Edit\Tab;


class Main extends \Magento\User\Block\User\Edit\Tab\Main {

    protected function _prepareForm()
    {
        parent::_prepareForm();
        /** @var $model \Magento\User\Model\User */
        $model = $this->_coreRegistry->registry('permissions_user');
        $form = $this->getForm();

        $fieldset = $form->addFieldset('digiselfie_fieldset', array('legend'=>__('DigiSelfie Information')));

        $fieldset->addField('digiselfie_login_id', 'text', array(
            'label'    => __('DigiSelfie ID'),
            'name'     => 'digiselfie_login_id',
            'id'       => 'digiselfie_id',
            'required' => false,
            'value'    => '',
            'disabled' => false
        ));

        $data = $model->getData();
        unset($data['password']);
        unset($data[self::CURRENT_USER_PASSWORD_FIELD]);

        $form->setValues($data);

        $this->setForm($form);

        return $this;
    }
}
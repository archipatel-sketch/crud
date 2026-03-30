
<?php
return [
    'users' => [
        [
            'label' => 'Name',
            'name' => 'name',
            'type' => 'text',
            'rules' => 'required|string|max:255',
            'visible' => true,
        ],
        [
            'label' => 'Email',
            'name' => 'email',
            'type' => 'email',
            'rules' => 'required|email|unique:',
            'visible' => true,

        ],
        [
            'label' => 'Image',
            'name' => 'image',
            'type' => 'file',
            'visible' => true,
            'upload_type' => 'single',
            'rules' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ],
        [
            'label' => 'Gender',
            'name' => 'gender',
            'type' => 'radio',
            'values' => 'male|female|other',
            'default_checked' => 'male',
            'visible' => true,
            'rules' => 'nullable|in:male,female,other',
        ],
        [
            'label' => 'Language',
            'name' => 'language',
            'type' => 'checkbox',
            'values' => 'gujarati|english|hindi',
            'default_checked' => 'english',
            'visible' => true,
            'rules' => 'nullable|array',
        ],
        [
            'label' => 'City',
            'name' => 'city',
            'type' => 'select',
            'values' => 'surat|vapi|vadodara|ahmedabad|rajkot',
            'default_selected' => 'surat',
            'rules' => 'required|in:surat,vapi,vadodara,ahmedabad,rajkot',
            'visible' => true,
        ],
        [
            'label' => 'Join Date',
            'name' => 'join_date',
            'type' => 'date',
            'rules' => 'required|date|before_or_equal:today',
            'display_formate' => 'd-F-Y',
            'visible' => true,
        ],
        [
            'label' => 'Password',
            'name' => 'password',
            'type' => 'password',
            'rules' => 'required|min:6',
            'visible' => false,
        ],
    ],

    'posts' => [
        [
            'label' => 'Post Name',
            'name' => 'post_name',
            'type' => 'text',
            'visible' => true,
            'rules' => 'required|string|min:3',
        ],
        [
            'label' => 'Content',
            'name' => 'content',
            'type' => 'textarea',
            'visible' => true,
            'rules' => 'required|string|min:10',
        ],
        [
            'label' => 'image',
            'name' => 'image',
            'type' => 'file',
            'visible' => true,
            'upload_type' => 'multiple',
            'rules' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ],
    ],

];

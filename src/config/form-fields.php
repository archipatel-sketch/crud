
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
            'rules' => 'required|email',
            'visible' => true,

        ],
        [
            'label' => 'image',
            'name' => 'image',
            'type' => 'file',
            'visible' => true,
            'upload_type' => 'single',
            'rules' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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

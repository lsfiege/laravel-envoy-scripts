# Configure Server with SSH for GitLab

Docs of [SSH GitLab](https://gitlab.com/help/ssh/README#generating-a-new-ssh-key-pair)

In your server, generate new SSH key with name id_gitlab wihtout password

```bash
ssh-keygen -t rsa -b 4096 -C "youremail@example.com" -f ~/.ssh/id_gitlab
```

now add the public key (id_gitlab.pub) to GitLab

add in ~/.ssh/config

```bash
Host gitlab.com
    Hostname gitlab.com
    PreferredAuthentications publickey
    IdentityFile ~/.ssh/id_gitlab
```

add the identity
```bash
eval $(ssh-agent -s)
ssh-add ~/.ssh/id_gitlab
```

now test connection with

```bash
ssh -T git@gitlab.com
```
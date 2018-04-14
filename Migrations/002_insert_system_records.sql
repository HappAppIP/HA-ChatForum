INSERT INTO branches
(local_branch_id, ext_branch_id, branch_name)
VALUES
  (1, 0, 'System branch used too maintain constraints');

INSERT INTO companies
(local_company_id, ext_company_id, company_name)
VALUES
  (1, 0, 'System branch used too maintain constraints');

INSERT INTO userTokens(token_id, forum_type, local_branch_id, local_company_id, ext_user_id, user_name, avatar_url, token, token_ttl)
    VALUES (1, 'SYSTEM', 1, 1, 0, 'root', '', 'This is a system user only.', '1970-01-02');

INSERT INTO categories
  (category_id, token_id, local_branch_id, parent_id, title, description)
    VALUES
  (1, 1, 1, 0, 'System category', 'Used to maintain constraints');

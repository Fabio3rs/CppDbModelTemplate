#include "../Database/CSql.hpp"
#include "<?= $MODEL_NAME ?>.hpp"

static void get_this_model_data(<?= $MODEL_NAME ?> &val,
                                  unique_resultset_t &res)
{
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    val.', $v->name, ' = ', nullablefieldtype_get($v), ';', "\n";
    }
    ?>
}

auto <?= $MODEL_NAME ?>::findByID(uint64_t fid) -> bool {
    auto sqlconn = std::reinterpret_pointer_cast<sql::Connection>(usingconn);

    auto select_statement = unique_prepstatement_t(sqlconn->prepareStatement(
        "SELECT * FROM <?= $TABLE_NAME ?> WHERE id = ?;"));

    select_statement->setUInt64(1, fid);

    auto res = unique_resultset_t(select_statement->executeQuery());

    if (!res->next()) {
        return false;
    }

    id = res->getUInt64("id");
    get_this_model_data(*this, res);

    return true;
}

<?php
if (isset($FINDABLES) && is_array($FINDABLES) && count($FINDABLES) > 0)
{
    foreach ($FINDABLES as $k => $v)
    {
        $fielddata = find_field($FIELDS, $v);
        $queryt = field_type_cpp_query($fielddata[1]->type);
?>
auto <?= $MODEL_NAME ?>::findBy<?= ucfirst($v) ?>(<?= $queryt ?> value) -> bool {
    auto sqlconn = std::reinterpret_pointer_cast<sql::Connection>(usingconn);

    auto select_statement = unique_prepstatement_t(sqlconn->prepareStatement(
        "SELECT * FROM <?= $TABLE_NAME ?> WHERE <?= $v ?> = ?;"));

    select_statement<?= field_type_gen_set($fielddata[1]->type, $v, 'value', 1, '') ?>;

    auto res = unique_resultset_t(select_statement->executeQuery());

    if (!res->next()) {
        return false;
    }

    id = res->getUInt64("id");
    get_this_model_data(*this, res);

    return true;
}
<?php
    }
}
?>

static void
fill_prepared_statement_base_data(const <?= $MODEL_NAME ?> &val,
                                  unique_prepstatement_t &prepstmt) {
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    ', nullablefieldtype_set($v, 'val.' . $v->name, $k + 1), ';', "\n";
    }
    ?>
}

void <?= $MODEL_NAME ?>::create() {
    auto sqlconn = std::reinterpret_pointer_cast<sql::Connection>(usingconn);

    auto create_statement = unique_prepstatement_t(sqlconn->prepareStatement(
        "INSERT INTO <?= $TABLE_NAME ?> (<?= loop_fieldnames($FIELDS) ?>) VALUES "
        "(<?= loop_fieldnames_interr($FIELDS) ?>)"));

<?= extra_createupdate($FIELDS) ?>

    fill_prepared_statement_base_data(*this, create_statement);

    create_statement->execute();

    auto stmt = unique_statement_t(sqlconn->createStatement());
    auto res = unique_resultset_t(
        stmt->executeQuery("SELECT LAST_INSERT_ID() AS id;"));

    if (!res->next()) {
        throw std::runtime_error("Fail to create register");
    }

    id = res->getUInt64("id");
}

auto <?= $MODEL_NAME ?>::update() -> int {
    auto sqlconn = std::reinterpret_pointer_cast<sql::Connection>(usingconn);

<?= extra_update($FIELDS) ?>


    auto update_statement = unique_prepstatement_t(sqlconn->prepareStatement(
        "UPDATE <?= $TABLE_NAME ?> SET <?= loop_fieldnames_update($FIELDS) ?> "
        "WHERE id = ?;"));

    fill_prepared_statement_base_data(*this, update_statement);
    update_statement->setUInt64(<?= count($FIELDS) + 1 ?>, id);

    return update_statement->executeUpdate();
}

void <?= $MODEL_NAME ?>::save() {
    if (id == 0) {
        create();
    } else {
        update();
    }
}

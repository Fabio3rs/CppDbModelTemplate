#include "../Database/CSql.hpp"
#include "<?= $MODEL_NAME ?>.hpp"

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
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    ', $v->name, ' = ', nullablefieldtype_get($v), ';', "\n";
    }
    ?>

    return true;
}

static void
fill_prepared_statement_base_data(const <?= $MODEL_NAME ?> &val,
                                  unique_prepstatement_t &prepstmt) {
    prepstmt->setUInt64(1, val.tokenable_id);
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

#include "../Database/CSql.hpp"
#include "<?= $MODEL_NAME ?>.hpp"

Poco::Dynamic::VarHolderImpl<<?= $MODEL_NAME ?>>::VarHolderImpl(
    const <?= $MODEL_NAME ?> &val)
    : Poco::Dynamic::VarHolderImpl<JSON::Object>(*val.dump_json()) {}

Poco::Dynamic::VarHolderImpl<<?= $MODEL_NAME ?>>::~VarHolderImpl() {}

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

auto <?= $MODEL_NAME ?>::dump_json() const -> Poco::JSON::Object::Ptr
{
    Poco::JSON::Object::Ptr result(new Poco::JSON::Object);

    result->set("id", empty()? Poco::Dynamic::Var() :  Poco::Dynamic::Var(id));
        
    <?php
    foreach ($FIELDS as $k => $v)
    {
        if ($v->nullable)
        {
            ?>
            result->set("<?= $v->name ?>", <?= $v->name ?>? Poco::Dynamic::Var(<?= poco_json_field_type_convert($v, $v->name) ?>) :  Poco::Dynamic::Var());
            <?php
        }else
        {
            ?>
            result->set("<?= $v->name ?>", <?= poco_json_field_type_convert($v, $v->name) ?>);
            <?php
        }
    }
    ?>

    return result;
}

void <?= $MODEL_NAME ?>::from_json(const Poco::JSON::Object::Ptr &json)
{
    if (json.isNull())
    {
        return;
    }

    from_json(*json);
}

void <?= $MODEL_NAME ?>::from_json(const Poco::JSON::Object &json)
{
    <?php
        echo 'if (json.has("id") && !json.isNull("id")) {', "\n";
            echo 'id = json.getValue<uint64_t>("id");', "\n";
        echo "}else {id = 0;}\n";
    foreach ($FIELDS as $k => $v)
    {
        echo 'if (json.has("', $v->name,'") && !json.isNull("', $v->name,'")) {', "\n";
        switch ($v->type)
        {
            case 'timestamp':
                //echo 'CSql::system_time_to_str(' . $sname . ')';
                echo $v->name, ' = CSql::string_to_system_clock(json.getValue<std::string>("', $v->name, '"));', "\n";
                break;
            default:
                //return $sname . ($fieldstruct->nullable?  '.value()' : '');
                echo $v->name, ' = json.getValue<', field_type_cpp($v->type) ,'>("', $v->name, '");', "\n";

                break;
        }

        echo "}\n";
    }
    ?>
}

void <?= $MODEL_NAME ?>::save() {
    if (id == 0) {
        create();
    } else {
        update();
    }
}

#pragma once

#include <Poco/JSON/JSON.h>
#include <Poco/Dynamic/Var.h>

#include "../Database/GenericDBConnection.hpp"
#include <chrono>
#include <memory>
#include <optional>
#include <string>

class <?= $MODEL_NAME ?> {
    std::shared_ptr<GenericDBConnection> usingconn;

    auto update() -> int;
    void create();

public:
    /* Model fields */
    uint64_t id{0};
<?php
    foreach ($FIELDS as $k => $v)
    {
        echo '    ', nullablefieldtype($v), ' ', $v->name, extra_attribute_field($v), ';', "\n";
    }
    ?>

    /* Functions */
    auto empty() const -> bool { return id == 0; }

<?= declare_findable($FIELDS, $FINDABLES) ?>

    auto findByID(uint64_t fid) -> bool;
    void save();

    auto dump_json() -> Poco::JSON::Object::Ptr
    {
        Poco::JSON::Object::Ptr result(new Poco::JSON::Object);

        result->set("id", empty()? Poco::Dynamic::Var() :  Poco::Dynamic::Var(id));
        
        <?php
        foreach ($FIELDS as $k => $v)
        {
            if ($v->nullable)
            {
                ?>
                result->set("<?= $v->name ?>", <?= $v->name ?>? Poco::Dynamic::Var(<?= $v->name ?>.value()) :  Poco::Dynamic::Var());
                <?php
            }else
            {
                ?>
                result->set("<?= $v->name ?>", <?= $v->name ?>);
                <?php
            }
        }
        ?>

        return result;
    }

    <?= $MODEL_NAME ?>(std::shared_ptr<GenericDBConnection> conn)
        : usingconn(conn) {}
};

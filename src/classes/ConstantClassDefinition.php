<?php

//**********************************************************************************************
//                                        OpenCPUServer.php
//
// Author(s): Arnaud CHARLEROY
// OCPU for PHIS
// Copyright © - INRA - MISTEA - 2018
// Creation date: novembre 2015
// Contact:arnaud.charleroy@inra.fr, anne.tireau@inra.fr, pascal.neveu@inra.fr
// Last modification date: Feb. 08, 2018
// Subject: A class that represents an access to the openCPU server
//******************************************************************************

/**
 * @link http://www.inra.fr/
 * @copyright Copyright © INRA - 2018
 * @license https://www.gnu.org/licenses/agpl-3.0.fr.html AGPL-3.0
 */

namespace openSILEX\opencpuClientPHP\classes;

/**
 * ConstantClassDefinition class that represents documentation constants and format definitions
 * @link https://www.opencpu.org/api.html
 * @author Arnaud Charleroy <arnaud.charleroy@inra.fr>
 * @since 1.0
 */
class ConstantClassDefinition {
    const OPENCPU_SERVER_GET_METHOD = 'GET';
    const OPENCPU_SERVER_POST_METHOD = 'POST';

    /**
     * application/json jsonlite::toJSON format namespace
     */
    const OPENCPU_SESSION_JSON_FORMAT = 'json';

    /**
     * text/plain base::print format namespace
     */
    const OPENCPU_SESSION_PRINT_FORMAT = 'print';

    /**
     * text/csv	utils::write.csv format namespace
     */
    const OPENCPU_SESSION_CSV_FORMAT = 'csv';

    /**
     * application/ndjson jsonlite::stream_out format namespace
     */
    const OPENCPU_SESSION_NDJSON_FORMAT = 'ndjson';

    /**
     * text/markdown pander::pander format namespace
     */
    const OPENCPU_SESSION_MD_FORMAT = 'md';

    /**
     * text/plain utils::write.table format namespace
     */
    const OPENCPU_SESSION_TAB_FORMAT = 'tab';

    /**
     * application/octet-stream base::save format namespace
     */
    const OPENCPU_SESSION_RDA_FORMAT = 'rda';

    /**
     * application/octet-stream base::saveRDS format namespace
     */
    const OPENCPU_SESSION_RDS_FORMAT = 'rds';

    /**
     * pplication/x-protobuf protolite::serialize_pb  format namespace
     */
    const OPENCPU_SESSION_PB_FORMAT = 'pb';

    /**
     * application/feather feather::write_feather format namespace
     */
    const OPENCPU_SESSION_FEATHER_FORMAT = 'feather';

    /**
     * image/png grDevices::png format namespace
     */
    const OPENCPU_SESSION_PNG_FORMAT = 'png';

    /**
     * application/pdf grDevices::pdf format namespace
     */
    const OPENCPU_SESSION_PDF_FORMAT = 'pdf';

    /**
     * image/svg+xml grDevices::svg format namespace
     */
    const OPENCPU_SESSION_SVG_FORMAT = 'svg';
}
